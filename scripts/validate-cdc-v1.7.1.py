#!/usr/bin/env python3
import csv, json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
errors = []

def require(path: Path):
    if not path.exists() or path.stat().st_size == 0:
        errors.append(f"missing_or_empty:{path.relative_to(ROOT)}")
    return path

for rel in [
    'documentation/scope-matrix.csv',
    'documentation/capacity-envelope.json',
    'documentation/compatibility-matrix.json',
    'documentation/data-contract.json',
    'documentation/technical-design.md',
    'documentation/implementation-deviations.md',
    'documentation/dependency-manifest-v1.7.1.json',
    'documentation/sbom-v1.7.1.json',
    'documentation/schemas/evidence.schema.json',
    'documentation/schemas/scope-matrix.schema.json',
    'documentation/schemas/capacity-envelope.schema.json',
    'documentation/schemas/visual-scenarios.schema.json',
    'documentation/visual-scenarios-v1.7.1.json',
]: require(ROOT / rel)

levels = {'M0', 'M1', 'M2'}
impl = {'IMPLEMENTED', 'NOT_IMPLEMENTED', 'NOT_COVERED', 'DEPRECATED'}
tests = {'PASS', 'FAIL', 'NOT_RUN', 'SKIPPED', 'NO_BASELINE', 'NON_REPRODUCIBLE', 'BLOCKED'}
with (ROOT / 'documentation/scope-matrix.csv').open(newline='', encoding='utf-8') as fh:
    rows = list(csv.DictReader(fh))
if not rows: errors.append('scope_matrix_empty')
for n, row in enumerate(rows, 2):
    if row.get('level') not in levels: errors.append(f'scope_line_{n}:invalid_level')
    if row.get('implementation_status') not in impl: errors.append(f'scope_line_{n}:invalid_implementation_status')
    if row.get('test_status') not in tests: errors.append(f'scope_line_{n}:invalid_test_status')
    if row.get('level') in {'M0', 'M1'} and row.get('implementation_status') == 'NOT_COVERED':
        errors.append(f'scope_line_{n}:M0_M1_NOT_COVERED')

capacity = json.loads((ROOT / 'documentation/capacity-envelope.json').read_text())
if capacity['http_budgets']['p95_seconds'] > capacity['http_budgets']['p99_seconds']:
    errors.append('capacity:p95_greater_than_p99')
if capacity['memory']['threshold_bytes'] < capacity['memory']['minimum_available_bytes']:
    errors.append('capacity:threshold_below_minimum')
for name, budget in capacity['sql_budgets'].items():
    if not isinstance(budget.get('max_queries'), int): errors.append(f'capacity:{name}:missing_fixed_sql_budget')

manifest = json.loads((ROOT / 'documentation/dependency-manifest-v1.7.1.json').read_text())
for dep in manifest.get('dependencies', []):
    if not re.fullmatch(r'[0-9a-f]{64}', dep.get('sha256', '')): errors.append(f"dependency:{dep.get('name')}:invalid_sha256")

if errors:
    print(json.dumps({'status': 'FAIL', 'errors': errors}, ensure_ascii=False, indent=2))
    sys.exit(1)
print(json.dumps({'status': 'PASS', 'scope_rows': len(rows), 'dependencies': len(manifest['dependencies']), 'sql_budgets': len(capacity['sql_budgets'])}, ensure_ascii=False, indent=2))
