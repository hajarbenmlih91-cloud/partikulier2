#!/usr/bin/env python3
"""Measure client-side HTTP timing phases for the Partikulier staging contract.

This is a diagnostic tool only. It never sends credentials, cookies, writes, or
mutating requests. It adds a unique query parameter to reduce cache reuse and
records the response cache/upstream headers when available.
"""
from __future__ import annotations

import argparse
import json
import os
import statistics
import subprocess
import tempfile
import time
import urllib.parse
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

ROUTES = {
    "front-fr": "/fr/",
    "archive-fr": "/fr/annonces/",
    "rest-listings": "/wp-json/partikulier/v1/listings?locale=fr&per_page=24",
    "rest-root": "/wp-json/partikulier/v1/",
}

TIMING_FIELDS = (
    "time_namelookup",
    "time_connect",
    "time_appconnect",
    "time_pretransfer",
    "time_starttransfer",
    "time_total",
    "http_code",
    "remote_ip",
    "url_effective",
)


def cache_busted(path: str, token: str) -> str:
    separator = "&" if "?" in path else "?"
    return f"{path}{separator}pk_ttfb_probe={urllib.parse.quote(token, safe='')}"


def parse_headers(raw: str) -> dict[str, str]:
    """Return the last HTTP response headers, ignoring interim responses."""
    blocks = [b for b in raw.replace("\r\n", "\n").split("\n\n") if b.strip()]
    block = blocks[-1] if blocks else ""
    result: dict[str, str] = {}
    for line in block.splitlines():
        if ":" not in line:
            continue
        key, value = line.split(":", 1)
        result[key.strip().lower()] = value.strip()
    return result


def run_one(base: str, case: str, path: str, index: int, timeout: float) -> dict[str, Any]:
    token = f"{case}-{index}-{time.time_ns()}"
    url = urllib.parse.urljoin(base.rstrip("/") + "/", cache_busted(path, token).lstrip("/"))
    with tempfile.TemporaryDirectory(prefix="pk-ttfb-") as tmp:
        headers_path = Path(tmp) / "headers.txt"
        body_path = Path(tmp) / "body.bin"
        writeout = "\t".join(f"%{{{field}}}" for field in TIMING_FIELDS)
        command = [
            "curl",
            "--silent",
            "--show-error",
            "--http1.1",
            "--connect-timeout",
            str(min(timeout, 30.0)),
            "--max-time",
            str(timeout),
            "-H",
            "Cache-Control: no-cache, no-store",
            "-H",
            "Pragma: no-cache",
            "-D",
            str(headers_path),
            "-o",
            str(body_path),
            "-w",
            writeout,
            url,
        ]
        started = time.time()
        completed = subprocess.run(command, text=True, capture_output=True, check=False)
        elapsed_wall = time.time() - started
        values = completed.stdout.strip().split("\t")
        record: dict[str, Any] = {
            "case": case,
            "path": path,
            "url": url,
            "index": index,
            "measured_at_utc": datetime.now(timezone.utc).isoformat(),
            "curl_returncode": completed.returncode,
            "curl_stderr": completed.stderr[-1000:],
            "elapsed_wall_seconds": round(elapsed_wall, 6),
        }
        if len(values) == len(TIMING_FIELDS):
            for field, value in zip(TIMING_FIELDS, values):
                try:
                    record[field] = int(value) if field == "http_code" else float(value)
                except ValueError:
                    record[field] = value
            record["dns_seconds"] = record["time_namelookup"]
            record["tcp_seconds"] = max(0.0, record["time_connect"] - record["time_namelookup"])
            record["tls_seconds"] = max(0.0, record["time_appconnect"] - record["time_connect"])
            record["server_wait_ttfb_seconds"] = record["time_starttransfer"] - record["time_pretransfer"]
            record["download_seconds"] = max(0.0, record["time_total"] - record["time_starttransfer"])
            record["ttfb_seconds"] = record["time_starttransfer"]
            record["total_seconds"] = record["time_total"]
        raw_headers = headers_path.read_text(errors="replace") if headers_path.exists() else ""
        headers = parse_headers(raw_headers)
        record["headers"] = {
            "x-litespeed-cache": headers.get("x-litespeed-cache"),
            "x-hcdn-cache-status": headers.get("x-hcdn-cache-status"),
            "x-hcdn-upstream-rt": headers.get("x-hcdn-upstream-rt"),
            "server": headers.get("server"),
            "date": headers.get("date"),
            "content_type": headers.get("content-type"),
            "raw": raw_headers[-12000:],
        }
        return record


def percentile(values: list[float], quantile: float) -> float | None:
    if not values:
        return None
    ordered = sorted(values)
    position = (len(ordered) - 1) * quantile
    low = int(position)
    high = min(low + 1, len(ordered) - 1)
    return ordered[low] + (ordered[high] - ordered[low]) * (position - low)


def summarize(records: list[dict[str, Any]]) -> dict[str, Any]:
    good = [r for r in records if r.get("curl_returncode") == 0 and isinstance(r.get("ttfb_seconds"), (int, float))]
    result: dict[str, Any] = {
        "requests": len(records),
        "successful_samples": len(good),
        "http_2xx": sum(1 for r in records if 200 <= int(r.get("http_code", 0)) < 300),
    }
    for name in ("dns_seconds", "tcp_seconds", "tls_seconds", "server_wait_ttfb_seconds", "ttfb_seconds", "total_seconds"):
        values = [float(r[name]) for r in good if isinstance(r.get(name), (int, float))]
        result[f"{name}_p50"] = statistics.median(values) if values else None
        result[f"{name}_p95"] = percentile(values, 0.95)
        result[f"{name}_p99"] = percentile(values, 0.99)
        result[f"{name}_max"] = max(values) if values else None
    result["cache_headers_observed"] = sorted({
        json.dumps(r.get("headers", {}), sort_keys=True, ensure_ascii=False)
        for r in records
    })
    return result


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--base", default=os.environ.get("PK_TTFB_BASE", "https://blanchedalmond-reindeer-376379.hostingersite.com"))
    parser.add_argument("--samples", type=int, default=int(os.environ.get("PK_TTFB_SAMPLES", "20")))
    parser.add_argument("--timeout", type=float, default=float(os.environ.get("PK_TTFB_TIMEOUT", "30")))
    parser.add_argument("--region", default=os.environ.get("PK_MEASURE_REGION", "sandbox-unknown"))
    parser.add_argument("--output", type=Path)
    args = parser.parse_args()
    if args.samples < 1:
        parser.error("--samples must be >= 1")

    measured_at = datetime.now(timezone.utc).isoformat()
    records: list[dict[str, Any]] = []
    summaries: dict[str, Any] = {}
    for case, path in ROUTES.items():
        case_records = [run_one(args.base, case, path, i, args.timeout) for i in range(1, args.samples + 1)]
        records.extend(case_records)
        summaries[case] = {"path": path, **summarize(case_records)}
        print(json.dumps({"case": case, **summaries[case]}, ensure_ascii=False))

    payload = {
        "schema": "partikulier-ttfb-breakdown-v1",
        "candidate": os.environ.get("PK_COMMIT", "unspecified"),
        "base": args.base,
        "region": args.region,
        "measured_at_utc": measured_at,
        "samples_per_route": args.samples,
        "cache_policy": "Cache-Control: no-cache, no-store; unique pk_ttfb_probe query parameter",
        "routes": ROUTES,
        "summaries": summaries,
        "raw_records": records,
    }
    if args.output:
        args.output.write_text(json.dumps(payload, indent=2, ensure_ascii=False) + "\n")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
