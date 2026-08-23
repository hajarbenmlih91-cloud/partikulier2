#!/usr/bin/env bash
set -Eeuo pipefail
BASE="${PK_BASE:-http://localhost:8090}"
REPORT="${PK_OBSERVABILITY_REPORT:-documentation/observability-v1.7.1.json}"
COMMIT="${PK_COMMIT:-$(git rev-parse HEAD 2>/dev/null || true)}"
[[ "$COMMIT" =~ ^[0-9a-f]{40}$ ]] || { echo 'PK_COMMIT invalide' >&2; exit 2; }
mkdir -p "$(dirname "$REPORT")"
health=$(curl -fsS "$BASE/wp-json/partikulier/v1/health")
status=$(jq -r '.status' <<<"$health")
forbidden=$(jq -r 'to_entries[] | select(.key|test("secret|token|password|path|host|stack";"i")) | .key' <<<"$health" | paste -sd, -)
log_status='NOT_CHECKED'
if [ -n "${PK_SERVER_LOG:-}" ] && [ -f "$PK_SERVER_LOG" ]; then
  log_status='PRESENT'
fi
if [ "$status" = ok ] && [ -z "$forbidden" ]; then
  ok=true
else
  ok=false
fi
payload=$(jq -n --arg commit "$COMMIT" --arg status "$status" --arg forbidden "$forbidden" --arg log "$log_status" --arg version "${PK_VERSION:-1.7.1}" --arg run "${PK_RUN_ID:-local}" --arg source_ref "${GITHUB_REF:-local}" --argjson ok "$ok" '{test_id:"OBS-HEALTH-001",candidate_version:$version,source_commit:$commit,source_ref:$source_ref,run_id:$run,status:($ok|if . then "PASS" else "FAIL" end),exit_code:($ok|if . then 0 else 1 end),health:$status,forbidden_keys:($forbidden|if .=="" then [] else split(",") end),server_log:$log,limitations:["alert delivery and production dashboard are separate operational approvals"]}')
printf '%s\n' "$payload" | tee "$REPORT"
[ "$ok" = true ]
