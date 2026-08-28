#!/usr/bin/env python3
"""Validate the measurable objective contract and optional execution results."""
from __future__ import annotations

import argparse
import json
import re
import sys
from pathlib import Path

EXPECTED_STATUSES = {"PASS", "FAIL", "BLOCKED", "INVALID", "NOT_RUN", "NOT_AUTHORIZED"}
EXPECTED_CODES = {
    "PASS": 0,
    "FAIL": 1,
    "BLOCKED": 2,
    "INVALID": 2,
    "NOT_RUN": 3,
    "NOT_AUTHORIZED": 3,
}
REQUIRED_OBJECTIVE_FIELDS = {
    "id",
    "name",
    "target",
    "required_evidence",
    "command",
    "expected_exit",
    "pass_rule",
    "environment_block_rule",
}
OBJECTIVE_PREFIXES = ("AUDIT-", "SIM-")


def fail(errors: list[str], message: str) -> None:
    errors.append(message)


def validate_contract(contract: dict) -> list[str]:
    errors: list[str] = []
    if contract.get("contract_id") != "partikulier-objectives-20":
        fail(errors, "contract_id_invalid")
    if contract.get("contract_version") != "1.0.0":
        fail(errors, "contract_version_invalid")
    if set(contract.get("status_values", [])) != EXPECTED_STATUSES:
        fail(errors, "status_values_invalid")
    if contract.get("exit_codes") != EXPECTED_CODES:
        fail(errors, "exit_codes_invalid")
    if "Any FAIL" not in contract.get("global_rule", ""):
        fail(errors, "global_rule_missing_fail_block")
    objectives = contract.get("objectives")
    if not isinstance(objectives, list) or len(objectives) != 20:
        fail(errors, f"objective_count_invalid:{len(objectives) if isinstance(objectives, list) else 'not-list'}")
        return errors
    seen: set[str] = set()
    for index, objective in enumerate(objectives):
        if not isinstance(objective, dict):
            fail(errors, f"objective_not_object:{index}")
            continue
        missing = REQUIRED_OBJECTIVE_FIELDS - objective.keys()
        for field in sorted(missing):
            fail(errors, f"objective_field_missing:{index}:{field}")
        oid = objective.get("id")
        if not isinstance(oid, str) or not oid.startswith(OBJECTIVE_PREFIXES):
            fail(errors, f"objective_id_invalid:{index}")
        elif oid in seen:
            fail(errors, f"objective_id_duplicate:{oid}")
        else:
            seen.add(oid)
        if objective.get("expected_exit") != 0:
            fail(errors, f"objective_expected_exit_must_be_zero:{oid}")
        if not isinstance(objective.get("required_evidence"), list) or not objective.get("required_evidence"):
            fail(errors, f"objective_evidence_invalid:{oid}")
        for field in ("target", "command", "pass_rule", "environment_block_rule"):
            if not isinstance(objective.get(field), str) or not objective.get(field).strip():
                fail(errors, f"objective_text_invalid:{oid}:{field}")
    audit_ids = {f"AUDIT-{i:02d}" for i in range(1, 11)}
    sim_ids = {f"SIM-{i:02d}" for i in range(1, 11)}
    if seen != audit_ids | sim_ids:
        fail(errors, f"objective_id_set_invalid:missing={sorted((audit_ids | sim_ids) - seen)}:extra={sorted(seen - audit_ids - sim_ids)}")
    return errors


def validate_results(result_path: Path) -> list[str]:
    errors: list[str] = []
    data = json.loads(result_path.read_text(encoding="utf-8"))
    rows = data.get("results", data.get("objectives", []))
    if not isinstance(rows, list):
        return ["result_rows_not_list"]
    for index, row in enumerate(rows):
        status = row.get("status")
        exit_code = row.get("exit_code")
        if status not in EXPECTED_STATUSES:
            errors.append(f"result_status_invalid:{index}:{status}")
            continue
        if exit_code != EXPECTED_CODES[status]:
            errors.append(f"result_exit_mismatch:{index}:{status}:{exit_code}")
        if status != "PASS":
            errors.append(f"release_gate_not_pass:{row.get('id', row.get('objective', index))}:{status}")
    return errors


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("contract", type=Path)
    parser.add_argument("--results", type=Path)
    args = parser.parse_args()
    errors = validate_contract(json.loads(args.contract.read_text(encoding="utf-8")))
    if args.results:
        errors.extend(validate_results(args.results))
    payload = {
        "test_id": "OBJECTIVES-CONTRACT-001",
        "contract": str(args.contract),
        "results": str(args.results) if args.results else None,
        "status": "PASS" if not errors else "FAIL",
        "errors": errors,
        "exit_code_rule": EXPECTED_CODES,
        "go_rule": "GO forbidden unless every objective status is PASS and exit_code is 0.",
    }
    print(json.dumps(payload, ensure_ascii=False, indent=2))
    return 0 if not errors else 1


if __name__ == "__main__":
    sys.exit(main())
