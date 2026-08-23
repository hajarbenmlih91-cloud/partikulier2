#!/usr/bin/env python3
"""Compute honest CDC v1.7.1 qualification labels from the scope matrix."""
import argparse
import csv
import json
import os
import re
import sys
from datetime import datetime, timezone
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
MATRIX = ROOT / "documentation" / "scope-matrix.csv"
BLOCKING_IMPL = {"NOT_IMPLEMENTED", "NOT_COVERED", "DEPRECATED"}
BLOCKING_TEST = {"FAIL", "NOT_RUN", "SKIPPED", "NO_BASELINE", "NON_REPRODUCIBLE", "BLOCKED"}

parser = argparse.ArgumentParser()
parser.add_argument("--output", default="documentation/qualification-v1.7.1.json")
parser.add_argument("--commit", default=os.environ.get("PK_COMMIT", ""))
parser.add_argument("--package-sha256", default=os.environ.get("PK_PACKAGE_SHA256", ""))
args = parser.parse_args()

errors = []
if not re.fullmatch(r"[0-9a-f]{40}", args.commit):
    errors.append("source_commit_missing_or_invalid")
if args.package_sha256 and not re.fullmatch(r"[0-9a-f]{64}", args.package_sha256):
    errors.append("package_sha256_invalid")

with MATRIX.open(newline="", encoding="utf-8") as handle:
    rows = list(csv.DictReader(handle))
if not rows:
    errors.append("scope_matrix_empty")

m0 = [r for r in rows if r.get("level") == "M0"]
m1 = [r for r in rows if r.get("level") == "M1"]
m2 = [r for r in rows if r.get("level") == "M2"]

def blockers(group):
    return [
        {"capability": r.get("capability"), "implementation_status": r.get("implementation_status"), "test_status": r.get("test_status"), "owner": r.get("owner"), "notes": r.get("notes")}
        for r in group
        if r.get("implementation_status") in BLOCKING_IMPL or r.get("test_status") in BLOCKING_TEST
    ]

m0_blockers = blockers(m0)
m1_blockers = blockers(m1)
commercial_blockers = [r for r in m2 if r.get("capability") == "Commercial approval" and (r.get("implementation_status") != "IMPLEMENTED" or r.get("test_status") != "PASS")]
capacity_blockers = [r for r in m1 if r.get("capability") in {"Load test 1000 listings", "HTTP p95 p99 gate"} and (r.get("implementation_status") != "IMPLEMENTED" or r.get("test_status") != "PASS")]

technical = "PASS" if not m0_blockers and not errors else "FAIL"
ux_content = "PASS" if not m1_blockers and not errors else "FAIL"
commercial = "PASS" if not commercial_blockers and not errors else "FAIL"
ultra = "PASS" if technical == "PASS" and ux_content == "PASS" and commercial == "PASS" and not capacity_blockers else "FAIL"

if ultra == "PASS":
    decision = "ULTRA_PREMIUM"
elif technical == "PASS":
    decision = "CONFORME_TECHNIQUEMENT_SOUS_RESERVES"
else:
    decision = "RELEASE_CANDIDATE"

payload = {
    "test_id": "QUALIFICATION-REPORT-001",
    "candidate_version": "1.7.1",
    "product_version": "6.17.17",
    "source_commit": args.commit,
    "source_ref": os.environ.get("GITHUB_REF", "local"),
    "run_id": os.environ.get("GITHUB_RUN_ID", "local"),
    "generated_at_utc": datetime.now(timezone.utc).isoformat().replace("+00:00", "Z"),
    "status": "PASS" if ultra == "PASS" and not errors else ("FAIL" if errors or technical == "FAIL" else "BLOCKED"),
    "decision": decision,
    "labels": {
        "TECHNICAL_STATUS": technical,
        "RELEASE_STATUS": "CANDIDATE" if technical == "PASS" else "BLOCKED",
        "TECHNICAL_RELEASE_CANDIDATE": technical,
        "UX_CONTENT": ux_content,
        "COMMERCIAL_RELEASE": commercial,
        "ULTRA_PREMIUM": ultra,
    },
    "scope_counts": {"M0": len(m0), "M1": len(m1), "M2": len(m2)},
    "blockers": {"source": errors, "M0": m0_blockers, "M1": m1_blockers, "commercial": commercial_blockers, "capacity": capacity_blockers},
    "package_sha256": args.package_sha256 or None,
    "human_validation": "PENDING_NOT_SIMULATED",
    "release_policy": "No tag or release is authorized by this report alone.",
    "status_semantics": "PASS means all qualification labels pass; BLOCKED means the report is valid but required scope or human approval remains outstanding.",
}
output = ROOT / args.output
output.parent.mkdir(parents=True, exist_ok=True)
output.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n", encoding="utf-8")
print(json.dumps(payload, ensure_ascii=False, indent=2))
# A report can be generated while a candidate is pending; only malformed provenance is a script error.
sys.exit(0 if not errors else 1)
