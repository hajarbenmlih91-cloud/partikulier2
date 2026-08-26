#!/usr/bin/env python3
"""Validate human approval records without inventing reviewer identity or signatures."""
from __future__ import annotations

import argparse
import json
import os
import re
import sys
from pathlib import Path
from typing import Any

VERSION = os.environ.get("PK_VERSION", "6.17.22")
SHA40 = re.compile(r"^[0-9a-f]{40}$")
SHA64 = re.compile(r"^[0-9a-f]{64}$")
UTC = re.compile(r"Z$")


class Blocked(Exception):
    pass


def require(value: Any, label: str) -> None:
    if value is None or value == "" or value == [] or value == {}:
        raise Blocked(f"{label} is missing")


def load(path: Path) -> dict[str, Any]:
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        raise Blocked(f"cannot read {path}: {exc}") from exc
    if not isinstance(data, dict):
        raise Blocked(f"{path} root must be an object")
    return data


def validate_record(data: dict[str, Any], expected_commit: str, expected_package: str, label: str) -> int:
    if data.get("candidate_version") != VERSION:
        raise Blocked(f"{label}.candidate_version must be {VERSION}")
    if data.get("status") != "APPROVED":
        raise Blocked(f"{label}.status is {data.get('status')!r}; only APPROVED can close the human gate")
    if data.get("source_commit") != expected_commit:
        raise Blocked(f"{label}.source_commit does not match expected commit")
    if data.get("package_sha256") != expected_package:
        raise Blocked(f"{label}.package_sha256 does not match expected package SHA")

    records = data.get("approvals") or data.get("attestations")
    require(records, f"{label}.approvals_or_attestations")
    if not isinstance(records, list):
        records = list(records.values()) if isinstance(records, dict) else []
    if not records:
        raise Blocked(f"{label} has no approval records")
    minimums = {"ux_content": 3, "native_language": 3, "visual_design": 1}
    if len(records) < minimums.get(label, 1):
        raise Blocked(f"{label} has {len(records)} records; minimum is {minimums[label]}")
    if label == "native_language":
        languages = {str(record.get("language", "")).lower() for record in records if isinstance(record, dict)}
        missing_languages = {"fr", "en", "ar"} - languages
        if missing_languages:
            raise Blocked(f"native_language is missing languages: {sorted(missing_languages)}")

    for index, record in enumerate(records, 1):
        prefix = f"{label}[{index}]"
        if not isinstance(record, dict):
            raise Blocked(f"{prefix} must be an object")
        for field in ("reviewer_identity", "reviewer_role", "conflict_of_interest", "reviewed_commit", "reviewed_package_sha256", "decision", "reviewed_at_utc", "signature_method", "signature", "evidence_refs"):
            require(record.get(field), f"{prefix}.{field}")
        if record["reviewed_commit"] != expected_commit:
            raise Blocked(f"{prefix}.reviewed_commit does not match expected commit")
        if record["reviewed_package_sha256"] != expected_package:
            raise Blocked(f"{prefix}.reviewed_package_sha256 does not match expected package SHA")
        if record["decision"] != "APPROVE":
            raise Blocked(f"{prefix}.decision is not APPROVE")
        if not SHA40.fullmatch(record["reviewed_commit"]):
            raise Blocked(f"{prefix}.reviewed_commit is not a 40-character SHA")
        if not SHA64.fullmatch(record["reviewed_package_sha256"]):
            raise Blocked(f"{prefix}.reviewed_package_sha256 is not a 64-character SHA")
        if not UTC.search(str(record["reviewed_at_utc"])):
            raise Blocked(f"{prefix}.reviewed_at_utc must end in Z")
        if isinstance(record["evidence_refs"], list) and not record["evidence_refs"]:
            raise Blocked(f"{prefix}.evidence_refs is empty")
    return len(records)


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--commit", required=True)
    parser.add_argument("--package-sha256", required=True)
    parser.add_argument("--ux-record", type=Path, default=Path("documentation/ux-content-approval-v1.7.1.json"))
    parser.add_argument("--language-record", type=Path, default=Path("documentation/native-language-attestations-v1.7.1.json"))
    parser.add_argument("--visual-record", type=Path, default=Path("documentation/visual-design-system-review-v1.7.1.json"))
    parser.add_argument("--output", type=Path, default=Path("approval-verification-v1.7.1.json"))
    args = parser.parse_args()

    result: dict[str, Any] = {
        "test_id": "HUMAN-APPROVAL-VERIFY-001",
        "candidate_version": VERSION,
        "source_commit": args.commit,
        "package_sha256": args.package_sha256,
        "status": "BLOCKED",
        "human_validation": "PENDING_NOT_SIMULATED",
        "records": {},
    }
    try:
        if not SHA40.fullmatch(args.commit):
            raise Blocked("--commit must be an exact 40-character lowercase SHA")
        if not SHA64.fullmatch(args.package_sha256):
            raise Blocked("--package-sha256 must be an exact 64-character lowercase SHA")
        for label, path in (("ux_content", args.ux_record), ("native_language", args.language_record), ("visual_design", args.visual_record)):
            count = validate_record(load(path), args.commit, args.package_sha256, label)
            result["records"][label] = {"status": "PASS", "count": count, "path": str(path)}
        result["status"] = "PASS"
        result["human_validation"] = "AUTHENTIC_RECORDS_PRESENT"
    except Blocked as exc:
        result["error"] = str(exc)

    args.output.parent.mkdir(parents=True, exist_ok=True)
    args.output.write_text(json.dumps(result, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
    print(json.dumps(result, ensure_ascii=False, indent=2))
    return 0 if result["status"] == "PASS" else 1


if __name__ == "__main__":
    sys.exit(main())
