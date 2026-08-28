#!/usr/bin/env python3
"""Validate the measurable O1-O22 acceptance contract and execution results."""
from __future__ import annotations

import argparse
import json
import sys
from pathlib import Path

EXPECTED_STATUSES = {
    "PASS", "FAIL", "INVALID", "BLOCKED", "NOT_AUTHORIZED", "NOT_RUN",
    "INCONCLUSIVE", "PARTIAL", "MISSING",
}
EXPECTED_CODES = {
    "PASS": 0,
    "FAIL": 1,
    "INVALID": 2,
    "NOT_RUN": 2,
    "INCONCLUSIVE": 2,
    "PARTIAL": 2,
    "MISSING": 2,
    "BLOCKED": 75,
    "NOT_AUTHORIZED": 77,
}
REQUIRED_OBJECTIVE_FIELDS = {
    "id", "name", "target", "required_evidence", "command", "expected_exit",
    "pass_rule", "environment_block_rule",
}
EXPECTED_IDS = {f"O{i}" for i in range(1, 23)}


def validate_contract(contract: dict) -> list[str]:
    errors: list[str] = []
    if contract.get("contract_id") != "partikulier-objectives-22":
        errors.append("contract_id_invalid")
    if contract.get("contract_version") != "1.1.0":
        errors.append("contract_version_invalid")
    if set(contract.get("status_values", [])) != EXPECTED_STATUSES:
        errors.append("status_values_invalid")
    if contract.get("exit_codes") != EXPECTED_CODES:
        errors.append("exit_codes_invalid")
    if "Any status other than PASS" not in contract.get("global_rule", ""):
        errors.append("global_rule_missing_nonpass_block")
    objectives = contract.get("objectives")
    if not isinstance(objectives, list) or len(objectives) != 22:
        errors.append(f"objective_count_invalid:{len(objectives) if isinstance(objectives, list) else 'not-list'}")
        return errors
    if contract.get("objective_count") != 22:
        errors.append("objective_count_field_invalid")
    seen: set[str] = set()
    for index, objective in enumerate(objectives):
        if not isinstance(objective, dict):
            errors.append(f"objective_not_object:{index}")
            continue
        missing = REQUIRED_OBJECTIVE_FIELDS - objective.keys()
        for field in sorted(missing):
            errors.append(f"objective_field_missing:{index}:{field}")
        oid = objective.get("id")
        if not isinstance(oid, str) or oid not in EXPECTED_IDS:
            errors.append(f"objective_id_invalid:{index}:{oid}")
        elif oid in seen:
            errors.append(f"objective_id_duplicate:{oid}")
        else:
            seen.add(oid)
        if objective.get("expected_exit") != 0:
            errors.append(f"objective_expected_exit_must_be_zero:{oid}")
        if not isinstance(objective.get("required_evidence"), list) or not objective.get("required_evidence"):
            errors.append(f"objective_evidence_invalid:{oid}")
        for field in ("target", "command", "pass_rule", "environment_block_rule"):
            if not isinstance(objective.get(field), str) or not objective.get(field).strip():
                errors.append(f"objective_text_invalid:{oid}:{field}")
    if seen != EXPECTED_IDS:
        errors.append(f"objective_id_set_invalid:missing={sorted(EXPECTED_IDS - seen)}:extra={sorted(seen - EXPECTED_IDS)}")
    return errors


def validate_results(result_path: Path) -> list[str]:
    errors: list[str] = []
    try:
        data = json.loads(result_path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        return [f"result_unreadable:{exc.__class__.__name__}"]
    rows = data.get("results", data.get("objectives", []))
    if not isinstance(rows, list):
        return ["result_rows_not_list"]
    for index, row in enumerate(rows):
        if not isinstance(row, dict):
            errors.append(f"result_row_not_object:{index}")
            continue
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
    try:
        contract = json.loads(args.contract.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError) as exc:
        print(json.dumps({"status": "FAIL", "errors": [f"contract_unreadable:{exc.__class__.__name__}"], "exit_code": 1}, indent=2))
        return 1
    errors = validate_contract(contract)
    if args.results:
        errors.extend(validate_results(args.results))
    payload = {
        "test_id": "OBJECTIVES-CONTRACT-001",
        "contract": str(args.contract),
        "results": str(args.results) if args.results else None,
        "status": "PASS" if not errors else "FAIL",
        "errors": errors,
        "exit_code_rule": EXPECTED_CODES,
        "go_rule": "GO forbidden unless O1-O22 all have status PASS and exit_code 0.",
    }
    print(json.dumps(payload, ensure_ascii=False, indent=2))
    return 0 if not errors else 1


if __name__ == "__main__":
    sys.exit(main())
