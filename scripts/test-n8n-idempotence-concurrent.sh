#!/usr/bin/env bash
# Preuve de concurrence réelle I-3 : deux processus HTTP indépendants et simultanés.
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
URL="${PK_N8N_URL:-http://127.0.0.1:8090/wp-json/partikulier/v1/automation-event}"
SECRET="${PK_N8N_SECRET:-}"
KEY_ID="${PK_N8N_KEY_ID:-N}"
ROUNDS="${PK_N8N_RACE_ROUNDS:-5}"
[ -n "$SECRET" ] || { echo '{"passed":false,"error":"PK_N8N_SECRET is required"}'; exit 1; }
command -v curl >/dev/null || { echo '{"passed":false,"error":"curl is required"}'; exit 1; }
command -v php >/dev/null || { echo '{"passed":false,"error":"php is required"}'; exit 1; }

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
php -r 'if (strlen(base64_decode($argv[1], true) ?: "") < 32) exit(1);' "$SECRET" 2>/dev/null || {
  echo '{"passed":false,"error":"secret must decode to at least 32 bytes"}'; exit 1;
}

json_get() { php -r '$d=json_decode(stream_get_contents(STDIN),true); $v=$d[$argv[1]] ?? null; echo is_bool($v) ? ($v ? "true" : "false") : (string)$v;' "$1"; }
for round in $(seq 1 "$ROUNDS"); do
  event_id="n8n-concurrent-${round}-$(date +%s%N)"
  body="{\"event_id\":\"${event_id}\",\"event_type\":\"whatsapp_status\",\"source\":\"n8n\",\"payload\":{\"race\":true}}"
  timestamp="$(date +%s)"
  canonical="POST\n/partikulier/v1/automation-event\n${timestamp}\n${body}"
  signature="sha256=$(printf '%b' "$canonical" | openssl dgst -sha256 -hmac "$SECRET" -binary | od -An -vtx1 | tr -d ' \n')"
  rm -f "$TMP/a-$round" "$TMP/b-$round" "$TMP/a-code-$round" "$TMP/b-code-$round"
  curl -sS --max-time 15 -o "$TMP/a-$round" -w '%{http_code}' \
    -X POST "$URL" -H 'Content-Type: application/json' \
    -H "X-Partikulier-Automation: $SECRET" -H "X-Partikulier-Key-Id: $KEY_ID" \
    -H "X-Partikulier-Timestamp: $timestamp" -H "X-Partikulier-Signature: $signature" \
    --data-binary "$body" > "$TMP/a-code-$round" & p1=$!
  curl -sS --max-time 15 -o "$TMP/b-$round" -w '%{http_code}' \
    -X POST "$URL" -H 'Content-Type: application/json' \
    -H "X-Partikulier-Automation: $SECRET" -H "X-Partikulier-Key-Id: $KEY_ID" \
    -H "X-Partikulier-Timestamp: $timestamp" -H "X-Partikulier-Signature: $signature" \
    --data-binary "$body" > "$TMP/b-code-$round" & p2=$!
  wait "$p1"; wait "$p2"
  ca="$(cat "$TMP/a-code-$round")"; cb="$(cat "$TMP/b-code-$round")"
  da="$(json_get duplicate < "$TMP/a-$round")"; db="$(json_get duplicate < "$TMP/b-$round")"
  [ "$ca" = 200 ] && [ "$cb" = 200 ] || { echo "{\"passed\":false,\"round\":$round,\"error\":\"HTTP $ca/$cb\"}"; exit 1; }
  [ "$da" != "$db" ] || { echo "{\"passed\":false,\"round\":$round,\"error\":\"both responses have duplicate=$da\"}"; exit 1; }
  [ "$da" = true ] || [ "$db" = true ] || { echo "{\"passed\":false,\"round\":$round,\"error\":\"no duplicate response\"}"; exit 1; }
done
printf '{"passed":true,"rounds":%s,"invariants":["two independent HTTP processes","200/200 each round","one duplicate:false and one duplicate:true","no 500"]}\n' "$ROUNDS"
