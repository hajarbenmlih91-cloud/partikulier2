#!/usr/bin/env bash
# Preuve d’intégration HMAC HTTP réelle — deux curl indépendants par requête.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BASE="${PK_BASE:-http://localhost:8090}"
URL="${PK_HMAC_URL:-$BASE/wp-json/partikulier/v1/automation-event}"
ROUTE="/partikulier/v1/automation-event"
KEY_ID="${PK_HMAC_KEY_ID:-env}"
SECRET_B64="${PARTIKULIER_N8N_SECRET:-}"
VERSION="${PK_VERSION:-6.17.12}"
LOG="${PK_HMAC_LOG:-$ROOT/documentation/hmac-http-v${VERSION}.json}"
ROUNDS=5
[ -n "$SECRET_B64" ] || { echo 'PARTIKULIER_N8N_SECRET absent (valeur Base64 exigée)' >&2; exit 2; }
command -v curl >/dev/null || { echo 'curl absent' >&2; exit 2; }
command -v jq >/dev/null || { echo 'jq absent' >&2; exit 2; }

sign() {
  local timestamp="$1" body="$2"
  CANONICAL="POST
$ROUTE
$timestamp
$body" SECRET_B64="$SECRET_B64" php -r 'echo "sha256=" . hash_hmac("sha256", getenv("CANONICAL"), base64_decode(getenv("SECRET_B64")));'
}
request() {
  local output="$1" body="$2" timestamp="$3" secret="$4" signature="$5" key="$6"
  local code
  code=$(curl --silent --show-error --max-time 30 --output "$output" --write-out '%{http_code}' \
    -X POST "$URL" -H 'Content-Type: application/json' \
    -H "X-Partikulier-Automation: $secret" -H "X-Partikulier-Timestamp: $timestamp" \
    -H "X-Partikulier-Key-Id: $key" -H "X-Partikulier-Signature: $signature" \
    --data-binary "$body")
  printf '%s' "$code"
}

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
php "$ROOT/scripts/configure-hmac-fixture.php" >/dev/null
round_results=()
false_count=0
true_count=0
for round in $(seq 1 "$ROUNDS"); do
  event_id="n8n-cdc-${VERSION//./-}-$round-$(date +%s%N)"
  body=$(jq -cn --arg id "$event_id" '{event_id:$id,event_type:"whatsapp_inbound",source:"n8n",payload:{msg:"cdc-http"}}')
  ts=$(date +%s)
  sig=$(sign "$ts" "$body")
  code_a_file="$TMP/r${round}a.json"; code_b_file="$TMP/r${round}b.json"
  (request "$code_a_file" "$body" "$ts" "$SECRET_B64" "$sig" "$KEY_ID" >"$TMP/r${round}a.code") & pid_a=$!
  (request "$code_b_file" "$body" "$ts" "$SECRET_B64" "$sig" "$KEY_ID" >"$TMP/r${round}b.code") & pid_b=$!
  wait "$pid_a"; wait "$pid_b"
  code_a=$(cat "$TMP/r${round}a.code"); code_b=$(cat "$TMP/r${round}b.code")
  [ "$code_a" = 200 ] && [ "$code_b" = 200 ] || { echo "ROUND_${round}_FAIL_HTTP $code_a/$code_b"; exit 1; }
  dup_a=$(jq -r 'if has("duplicate") then (.duplicate|tostring) else "missing" end' "$code_a_file")
  dup_b=$(jq -r 'if has("duplicate") then (.duplicate|tostring) else "missing" end' "$code_b_file")
  if [ "$dup_a" = "$dup_b" ]; then echo "ROUND_${round}_FAIL_DUPLICATE $dup_a/$dup_b"; exit 1; fi
  [ "$dup_a" = "false" ] && false_count=$((false_count + 1))
  [ "$dup_a" = "true" ] && true_count=$((true_count + 1))
  [ "$dup_b" = "false" ] && false_count=$((false_count + 1))
  [ "$dup_b" = "true" ] && true_count=$((true_count + 1))
  round_results+=("$round:$code_a/$code_b:$dup_a/$dup_b")
done
[ "$false_count" -eq "$ROUNDS" ] && [ "$true_count" -eq "$ROUNDS" ] || { echo "DUPLICATE_COUNTS_FAIL false=$false_count true=$true_count"; exit 1; }

negative() {
  local label="$1" timestamp="$2" secret="$3" signature="$4" key="$5"
  local output="$TMP/negative-$label.json" body
  body=$(jq -cn --arg id "n8n-cdc-negative-$label-$(date +%s%N)" '{event_id:$id,event_type:"whatsapp_inbound",source:"n8n",payload:{msg:"negative"}}')
  local code
  code=$(curl --silent --show-error --max-time 30 --output "$output" --write-out '%{http_code}' \
    -X POST "$URL" -H 'Content-Type: application/json' -H "X-Partikulier-Automation: $secret" \
    -H "X-Partikulier-Timestamp: $timestamp" -H "X-Partikulier-Key-Id: $key" \
    -H "X-Partikulier-Signature: $signature" --data-binary "$body")
  printf '%s' "$code"
}
now=$(date +%s)
valid_body='{"event_id":"n8n-cdc-negative-signature","event_type":"whatsapp_inbound","source":"n8n","payload":{"msg":"negative"}}'
valid_sig=$(CANONICAL="POST
$ROUTE
$now
$valid_body" SECRET_B64="$SECRET_B64" php -r 'echo "sha256=" . hash_hmac("sha256", getenv("CANONICAL"), base64_decode(getenv("SECRET_B64")));')
invalid_secret=$(negative invalid-secret "$now" "invalid-secret" "$valid_sig" "$KEY_ID")
invalid_signature=$(negative invalid-signature "$now" "$SECRET_B64" 'sha256=0000000000000000000000000000000000000000000000000000000000000000' "$KEY_ID")
expired=$(negative expired "$((now - 601))" "$SECRET_B64" "$valid_sig" "$KEY_ID")
missing_header=$(curl --silent --show-error --max-time 30 --output "$TMP/negative-missing-header.json" --write-out '%{http_code}' \
  -X POST "$URL" -H 'Content-Type: application/json' -H "X-Partikulier-Timestamp: $now" -H "X-Partikulier-Key-Id: $KEY_ID" -H "X-Partikulier-Signature: $valid_sig" --data-binary "$valid_body")
[ "$invalid_secret" = 401 ] && [ "$invalid_signature" = 401 ] && [ "$expired" = 401 ] && [ "$missing_header" = 401 ] || { echo "NEGATIVE_FAIL $invalid_secret/$invalid_signature/$expired/$missing_header"; exit 1; }

jq -n --arg version "$VERSION" --arg base "$BASE" --arg url "$URL" --arg protocol 'POST\\nREST_ROUTE\\nTIMESTAMP\\nBODY; HMAC-SHA256 over Base64-decoded secret' \
  --argjson rounds "$ROUNDS" --argjson false_count "$false_count" --argjson true_count "$true_count" \
  --arg invalid_secret "$invalid_secret" --arg invalid_signature "$invalid_signature" --arg expired "$expired" --arg missing_header "$missing_header" \
  '{version:$version,base:$base,url:$url,canonicalization:$protocol,rounds:$rounds,concurrent:{http_200_each_round:true,duplicate_false:$false_count,duplicate_true:$true_count},negative:{invalid_secret:$invalid_secret,invalid_signature:$invalid_signature,expired_timestamp:$expired,missing_shared_header:$missing_header},secret_included:false}' | tee "$LOG"
