#!/usr/bin/env python3
"""Validate the immutable 30-scenario visual contract for CDC v1.7.1."""
import argparse
import hashlib
import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
CONTRACT = ROOT / "documentation" / "visual-scenarios-v1.7.1.json"
SCHEMA = ROOT / "documentation" / "schemas" / "visual-scenarios.schema.json"
PAGES = {"home", "archive", "single", "deposer", "favoris"}
LOCALES = {"fr", "en", "ar"}
VIEWPORTS = {"desktop", "mobile"}
EXPECTED = {f"{page}-{locale}-{viewport}" for page in PAGES for locale in LOCALES for viewport in VIEWPORTS}

parser = argparse.ArgumentParser()
parser.add_argument("--require-baselines", action="store_true")
args = parser.parse_args()
errors = []

if not CONTRACT.is_file():
    errors.append("contract_missing")
    data = {}
else:
    try:
        data = json.loads(CONTRACT.read_text(encoding="utf-8"))
    except json.JSONDecodeError as exc:
        errors.append(f"contract_invalid_json:{exc}")
        data = {}

if not SCHEMA.is_file():
    errors.append("schema_missing")

scenarios = data.get("scenarios", [])
if data.get("contract_id") != "visual-scenarios": errors.append("contract_id_invalid")
if data.get("contract_version") != "1.7.1": errors.append("contract_version_invalid")
if data.get("scenario_count") != 30: errors.append("scenario_count_invalid")
if data.get("locales") != ["fr", "en", "ar"]: errors.append("locales_invalid")
if data.get("baseline_policy", {}).get("regenerate_in_ci") is not False: errors.append("baseline_regeneration_must_be_false")
if data.get("baseline_policy", {}).get("capture_mode") != "viewport_only": errors.append("capture_mode_must_be_viewport_only")
if len(scenarios) != 30: errors.append(f"scenario_count_actual:{len(scenarios)}")

seen = set()
for index, scenario in enumerate(scenarios):
    sid = scenario.get("id")
    if sid in seen: errors.append(f"duplicate_id:{sid}")
    seen.add(sid)
    if sid not in EXPECTED: errors.append(f"unexpected_id:{sid}")
    page, locale, viewport = sid.split("-", 2) if isinstance(sid, str) and sid.count("-") == 2 else (None, None, None)
    if page not in PAGES or locale not in LOCALES or viewport not in VIEWPORTS: errors.append(f"invalid_dimensions:{index}")
    if scenario.get("page") != page: errors.append(f"page_mismatch:{sid}")
    if scenario.get("locale") != locale: errors.append(f"locale_mismatch:{sid}")
    if scenario.get("viewport") != viewport: errors.append(f"viewport_mismatch:{sid}")
    expected_dir = "rtl" if locale == "ar" else "ltr"
    if scenario.get("expected_dir") != expected_dir: errors.append(f"direction_mismatch:{sid}")
    if scenario.get("expected_http") != 200: errors.append(f"expected_http_not_200:{sid}")
    baseline = scenario.get("baseline", "")
    if baseline != f"tests/baselines-6.17.17/{sid}.png": errors.append(f"baseline_path_mismatch:{sid}")
    if args.require_baselines:
        baseline_path = ROOT / baseline
        if not baseline_path.is_file() or baseline_path.stat().st_size == 0:
            errors.append(f"baseline_missing:{sid}")

if seen != EXPECTED: errors.append(f"scenario_set_mismatch:missing={sorted(EXPECTED-seen)}")

status = "PASS" if not errors else "FAIL"
result = {
    "contract": "visual-scenarios-v1.7.1",
    "status": status,
    "scenario_count": len(scenarios),
    "expected_scenario_count": 30,
    "baseline_gate": "required" if args.require_baselines else "contract-only",
    "errors": errors,
}
print(json.dumps(result, ensure_ascii=False, indent=2))
sys.exit(0 if not errors else 1)
