#!/usr/bin/env python3
"""Execute the immutable CDC v1.7.1 capacity envelope against a disposable runtime."""
from __future__ import annotations

import argparse
import json
from collections import Counter
import os
import re
import subprocess
import sys
import tempfile
import threading
import time
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime, timezone
from pathlib import Path
from statistics import quantiles
from typing import Any

ROOT = Path(__file__).resolve().parents[1]
COMMIT_RE = re.compile(r"^[0-9a-f]{40}$")
MAX_RSS = 2 * 1024 * 1024 * 1024
P95_LIMIT = 1.5
P99_LIMIT = 3.0


def now() -> str:
    return datetime.now(timezone.utc).isoformat().replace("+00:00", "Z")


def run_checked(command: list[str], *, capture: bool = True) -> str:
    proc = subprocess.run(command, cwd=ROOT, text=True, capture_output=capture)
    if proc.returncode != 0:
        detail = (proc.stderr or proc.stdout or "").strip()
        raise RuntimeError(f"command failed ({proc.returncode}): {' '.join(command)}: {detail}")
    return (proc.stdout or "").strip()


def cgroup_memory_peak() -> int | None:
    paths = [
        Path("/sys/fs/cgroup/memory.peak"),
        Path("/sys/fs/cgroup/memory/memory.max_usage_in_bytes"),
        Path("/sys/fs/cgroup/memory.max_usage_in_bytes"),
    ]
    for path in paths:
        try:
            value = path.read_text().strip()
            if value.isdigit():
                return int(value)
        except OSError:
            pass
    return None


def process_jiffies() -> int:
    total = 0
    for proc in Path("/proc").glob("[0-9]*"):
        try:
            stat = (proc / "stat").read_text()
            name_end = stat.rfind(")")
            fields = stat[name_end + 2 :].split()
            comm = stat[stat.find("(") + 1 : name_end]
            if any(token in comm for token in ("php-fpm", "nginx", "mariadbd", "mysqld")):
                total += int(fields[11]) + int(fields[12])
        except (OSError, ValueError, IndexError):
            continue
    return total


class ResourceSampler:
    def __init__(self) -> None:
        self.samples: list[dict[str, float | int]] = []
        self.stop_event = threading.Event()
        self.thread: threading.Thread | None = None

    def start(self) -> None:
        self.stop_event.clear()
        self.thread = threading.Thread(target=self._run, daemon=True)
        self.thread.start()

    def stop(self) -> dict[str, Any]:
        self.stop_event.set()
        if self.thread:
            self.thread.join(timeout=5)
        if not self.samples:
            return {"cpu_average_percent": None, "cgroup_peak_rss_bytes": cgroup_memory_peak(), "samples": 0}
        elapsed = max(float(self.samples[-1]["monotonic"]) - float(self.samples[0]["monotonic"]), 0.001)
        delta = int(self.samples[-1]["jiffies"]) - int(self.samples[0]["jiffies"])
        hz = os.sysconf(os.sysconf_names["SC_CLK_TCK"])
        cpus = os.cpu_count() or 1
        cpu = (delta / hz) / elapsed / cpus * 100
        peak = cgroup_memory_peak()
        return {"cpu_average_percent": round(cpu, 3), "cgroup_peak_rss_bytes": peak, "samples": len(self.samples), "cpu_cores": cpus}

    def _run(self) -> None:
        while not self.stop_event.is_set():
            self.samples.append({"monotonic": time.monotonic(), "jiffies": process_jiffies(), "cgroup_peak_rss_bytes": cgroup_memory_peak() or 0})
            self.stop_event.wait(1)


def client_ip(index: int) -> str:
    return f"127.0.0.{2 + (index % 200)}"


def request(base: str, phase: str, index: int, auth: tuple[str, str, str] | None = None, write: bool = False) -> dict[str, Any]:
    url = f"{base}/wp-json/partikulier/v1/{'listings' if write else 'listings?locale=fr&per_page=24'}"
    cmd = ["curl", "-sS", "--max-time", "30", "--interface", client_ip(index), "-H", "Accept: application/json"]
    if write:
        cmd += ["-H", "Content-Type: application/json", "-d", json.dumps({"title": f"CDC capacity {phase} {index}", "description": "Disposable CDC capacity fixture", "locale": "fr", "price": 100, "area": 10})]
    if auth:
        cmd += ["--cookie", f"{auth[0]}={auth[1]}", "-H", f"X-WP-Nonce: {auth[2]}"]
    cmd += ["-o", "-", "-w", "\n__META__ %{http_code} %{time_total}", url]
    proc = subprocess.run(cmd, text=True, capture_output=True)
    marker = "\n__META__ "
    if marker not in proc.stdout:
        return {"status_code": 0, "seconds": 30.0, "error": (proc.stderr or "curl failed").strip(), "body": ""}
    body, meta = proc.stdout.rsplit(marker, 1)
    parts = meta.strip().split()
    code = int(parts[0]) if parts and parts[0].isdigit() else 0
    seconds = float(parts[1]) if len(parts) > 1 else 30.0
    item: dict[str, Any] = {"status_code": code, "seconds": seconds, "error": proc.stderr.strip() if proc.returncode else "", "body": body}
    if write and code == 201:
        try:
            item["created_id"] = int(json.loads(body).get("id", 0))
        except (ValueError, TypeError, json.JSONDecodeError):
            item["created_id"] = 0
    return item


def phase(base: str, name: str, rps: int, duration: int, credentials: list[tuple[str, str, str]] | None = None, write: bool = False) -> dict[str, Any]:
    sampler = ResourceSampler()
    results: list[dict[str, Any]] = []
    created_ids: list[int] = []
    results_lock = threading.Lock()
    started = now()
    phase_started = time.monotonic()
    deadline = phase_started + duration
    sampler.start()

    workers = min(max(1, rps), 200)
    interval = workers / rps
    def timed_worker(worker_id: int) -> None:
        sequence = 0
        next_at = phase_started + (worker_id / rps)
        while next_at < deadline:
            delay = next_at - time.monotonic()
            if delay > 0:
                time.sleep(delay)
            if time.monotonic() >= deadline:
                break
            auth = credentials[worker_id % len(credentials)] if credentials else None
            result = request(base, name, worker_id * 100000 + sequence, auth, write)
            sequence += 1
            with results_lock:
                results.append(result)
                if result.get("created_id"):
                    created_ids.append(int(result["created_id"]))
            next_at += interval

    with ThreadPoolExecutor(max_workers=workers) as pool:
        futures = [pool.submit(timed_worker, worker_id) for worker_id in range(workers)]
        for future in futures:
            future.result()
    resources = sampler.stop()
    times = sorted(float(row["seconds"]) for row in results if int(row.get("status_code", 0)) in range(200, 300))
    errors = sum(1 for row in results if int(row.get("status_code", 0)) not in range(200, 300))
    if times:
        qs = quantiles(times, n=100, method="inclusive") if len(times) >= 2 else [times[0]] * 99
        p95 = qs[94]
        p99 = qs[98]
        p50 = qs[49]
    else:
        p50 = p95 = p99 = None
    error_rate = errors / len(results) if results else 1.0
    status_counts = {str(code): count for code, count in sorted(Counter(int(row.get("status_code", 0)) for row in results).items())}
    error_samples = [{"status_code": int(row.get("status_code", 0)), "error": str(row.get("error", ""))[:240], "body": str(row.get("body", ""))[:240]} for row in results if int(row.get("status_code", 0)) not in range(200, 300)][:5]
    effective_rps = len(results) / max(duration, 1)
    rss = resources.get("cgroup_peak_rss_bytes")
    required_delivery = rps * 0.99 if name in {"sustained_read_10rps", "burst_read_25rps", "write_api_2rps"} else 0
    status = "PASS" if results and effective_rps >= required_delivery and error_rate <= 0.001 and p95 is not None and p95 <= P95_LIMIT and p99 <= P99_LIMIT and rss is not None and rss <= MAX_RSS and (resources.get("cpu_average_percent") is None or resources["cpu_average_percent"] <= 80) else "FAIL"
    return {"name": name, "status": status, "started_at_utc": started, "finished_at_utc": now(), "target_rps": rps, "duration_seconds": duration, "requests": len(results), "effective_rps": round(effective_rps, 3), "status_counts": status_counts, "error_samples": error_samples, "errors": errors, "error_rate": error_rate, "p50_seconds": p50, "p95_seconds": p95, "p99_seconds": p99, "resources": resources, "created_ids": created_ids, "concurrency_clients": workers}


def create_credentials(wp_dir: str, run_id: str, count: int = 50) -> tuple[list[tuple[str, str, str]], list[int]]:
    credentials: list[tuple[str, str, str]] = []
    cookie_name = run_checked(["wp", f"--path={wp_dir}", "eval", "echo LOGGED_IN_COOKIE;", "--allow-root"])
    if not cookie_name:
        raise RuntimeError("WordPress logged-in cookie name is unavailable")
    user_ids: list[int] = []
    suffix = re.sub(r"[^a-z0-9]", "", run_id.lower())[-12:] or "run"
    for index in range(count):
        username = f"pkload{suffix}{index:02d}"[:60]
        email = f"{username}@example.test"
        user_id = int(run_checked(["wp", f"--path={wp_dir}", "user", "create", username, email, "--role=author", "--user_pass=capacity-only-password", "--porcelain", "--allow-root"]))
        cookie = run_checked(["wp", f"--path={wp_dir}", "eval", f'echo wp_generate_auth_cookie({user_id}, time() + 3600, "logged_in");', "--allow-root"])
        nonce_eval = f'$_COOKIE[LOGGED_IN_COOKIE] = {json.dumps(cookie)}; wp_set_current_user({user_id}); echo wp_create_nonce("wp_rest");'
        nonce = run_checked(["wp", f"--path={wp_dir}", "eval", nonce_eval, "--allow-root"])
        credentials.append((cookie_name, cookie, nonce))
        user_ids.append(user_id)
    return credentials, user_ids


def cleanup(wp_dir: str, ids: list[int], user_ids: list[int]) -> None:
    for post_id in sorted(set(ids)):
        subprocess.run(["wp", f"--path={wp_dir}", "post", "delete", str(post_id), "--force", "--allow-root"], cwd=ROOT, text=True, capture_output=True)
    for user_id in sorted(set(user_ids)):
        subprocess.run(["wp", f"--path={wp_dir}", "user", "delete", str(user_id), "--yes", "--reassign=1", "--allow-root"], cwd=ROOT, text=True, capture_output=True)


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--base", default=os.environ.get("PK_BASE", "http://127.0.0.1:8090"))
    parser.add_argument("--wp-dir", default=os.environ.get("PK_WP_DIR", ""))
    parser.add_argument("--output", default=os.environ.get("PK_CAPACITY_REPORT", "documentation/capacity-envelope-v1.7.1.json"))
    parser.add_argument("--scale", type=float, default=float(os.environ.get("PK_CAPACITY_SCALE", "1")))
    args = parser.parse_args()
    commit = os.environ.get("PK_COMMIT", "")
    run_id = os.environ.get("PK_RUN_ID", os.environ.get("GITHUB_RUN_ID", "local"))
    payload: dict[str, Any] = {"test_id": "CAPACITY-ENVELOPE-001", "candidate_version": "6.17.17", "source_commit": commit, "source_ref": os.environ.get("GITHUB_REF", "local"), "run_id": run_id, "started_at_utc": now(), "status": "FAIL", "scale": args.scale, "scale_is_contractual": args.scale == 1, "phases": [], "saturation_probe": [], "cleanup": {}, "limitations": ["CPU is normalized over available CPUs and cgroup memory is used when exposed by the runner."]}
    all_created: list[int] = []
    user_ids: list[int] = []
    try:
        if not COMMIT_RE.fullmatch(commit):
            raise RuntimeError("PK_COMMIT must be an exact 40-character lowercase SHA")
        if not args.wp_dir or not Path(args.wp_dir, "wp-load.php").is_file():
            raise RuntimeError("--wp-dir must point to a WordPress runtime")
        if args.scale <= 0:
            raise RuntimeError("scale must be positive")
        read_base = f"{args.base.rstrip('/') }"
        warmup = 30
        for i in range(warmup):
            request(read_base, "warmup", i)
        credentials, user_ids = create_credentials(args.wp_dir, run_id)
        exact = lambda seconds: max(1, int(round(seconds * args.scale)))
        payload["phases"].append(phase(read_base, "sustained_read_10rps", 10, exact(900)))
        payload["phases"].append(phase(read_base, "burst_read_25rps", 25, exact(60)))
        write_phase = phase(read_base, "write_api_2rps", 2, exact(900), credentials, True)
        all_created.extend(write_phase.pop("created_ids", []))
        payload["phases"].append(write_phase)
        session_sampler = ResourceSampler()
        session_sampler.start()
        with ThreadPoolExecutor(max_workers=50) as pool:
            session_results = list(pool.map(lambda i: request(read_base, "concurrent_sessions", i), range(50)))
        session_resources = session_sampler.stop()
        session_errors = sum(1 for row in session_results if row["status_code"] not in range(200, 300))
        payload["phases"].append({"name": "concurrent_sessions_50", "status": "PASS" if session_errors == 0 else "FAIL", "target_concurrency": 50, "requests": 50, "errors": session_errors, "resources": session_resources})
        for rps in (50, 100, 200, 400, 800):
            probe = phase(read_base, f"saturation_probe_{rps}rps", rps, max(5, exact(10)))
            payload["saturation_probe"].append(probe)
            if probe["status"] != "PASS":
                break
        failed_probe = next((row for row in payload["saturation_probe"] if row["status"] != "PASS"), None)
        payload["saturation_point"] = {"observed": failed_probe is not None, "rps": failed_probe.get("target_rps") if failed_probe else None, "reason": "first non-passing probe" if failed_probe else "not reached before maximum probe"}
        all_pass = args.scale == 1 and all(row.get("status") == "PASS" for row in payload["phases"]) and failed_probe is not None and all(row.get("resources", {}).get("cgroup_peak_rss_bytes") is not None and int(row["resources"]["cgroup_peak_rss_bytes"]) <= MAX_RSS for row in payload["phases"])
        payload["status"] = "PASS" if all_pass else "FAIL"
    except Exception as exc:
        payload["error"] = str(exc)
    finally:
        if args.wp_dir and (all_created or user_ids):
            cleanup(args.wp_dir, all_created, user_ids)
        payload["cleanup"] = {"created_posts_deleted": len(set(all_created)), "temporary_users_created": len(user_ids), "temporary_users_deleted": len(set(user_ids))}
        payload["finished_at_utc"] = now()
        output = ROOT / args.output
        output.parent.mkdir(parents=True, exist_ok=True)
        output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(payload, ensure_ascii=False, indent=2))
    return 0 if payload["status"] == "PASS" else 1


if __name__ == "__main__":
    sys.exit(main())
