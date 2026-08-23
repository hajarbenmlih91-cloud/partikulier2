#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPORT="${1:-$ROOT/documentation/secrets-scan-v1.7.1.json}"
mkdir -p "$(dirname "$REPORT")"
cd "$ROOT"
patterns='(AKIA[0-9A-Z]{16}|ASIA[0-9A-Z]{16}|ghp_[A-Za-z0-9]{30,}|github_pat_[A-Za-z0-9_]{30,}|-----BEGIN (RSA|EC|OPENSSH|PRIVATE) KEY-----|xox[baprs]-[A-Za-z0-9-]{20,})'
set +e
matches=$(git grep -nI -E "$patterns" -- ':!node_modules' ':!.runtime' ':!vendor-artifacts/*.zip' ':!tests/current' ':!tests/diff')
code=$?
set -e
if [ "$code" -eq 0 ]; then
  jq -n --arg status FAIL --arg matches "$matches" '{test_id:"SEC-SECRETS-001",status:$status,secret_included:true,matches:($matches|split("\n")),exit_code:1}' > "$REPORT"
  printf '%s\n' "$matches" >&2
  exit 1
fi
jq -n '{test_id:"SEC-SECRETS-001",status:"PASS",secret_included:false,matches:[],exit_code:0}' > "$REPORT"
printf 'SECRETS_SCAN=PASS\n'
