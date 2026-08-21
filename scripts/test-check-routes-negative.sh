#!/usr/bin/env bash
# Test négatif : le garde-fou doit mordre lorsqu'une route directe est ajoutée à un module métier.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
cp -a "$ROOT/theme" "$TMP/theme"
cp "$ROOT/scripts/check.sh" "$TMP/check.sh"
printf '\nregister_rest_route( "partikulier/v1", "/pirate", array() );\n' >> "$TMP/theme/inc/class-lead-retention.php"
set +e
output=$(PK_CHECK_ROOT="$TMP" bash "$TMP/check.sh" 2>&1)
status=$?
set -e
if [ "$status" -eq 0 ]; then
  printf '%s\n' '{"passed":false,"error":"check.sh accepted injected direct route"}'
  exit 1
fi
if ! grep -q 'ROUTE AUTOMATION DIRECTE INTERDITE' <<<"$output"; then
  printf '%s\n' '{"passed":false,"error":"check.sh failed for an unrelated reason"}'
  exit 1
fi
printf '%s\n' '{"passed":true,"invariant":"injected direct register_rest_route is rejected"}'
