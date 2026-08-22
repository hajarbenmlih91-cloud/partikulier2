#!/bin/bash
# Harnais HTTP HMAC Senior - Partikulier v6.17.9
BASE_URL=${PK_BASE:-"http://localhost:8097"}
ENDPOINT="/wp-json/partikulier/v1/automation-event"
SECRET_B64=${PK_N8N_SECRET_B64:-"cmVhbC1zZWNyZXQtYnJ1dC0xMjM="}
SECRET_RAW=$(echo -n "$SECRET_B64" | base64 -d)
KEY_ID="real-key-1"
EVENT_ID="n8n-test-$(date +%s)"
TIMESTAMP=$(date +%s)
PAYLOAD="{\"source\":\"n8n\",\"event_type\":\"whatsapp_inbound\",\"event_id\":\"${EVENT_ID}\"}"
CANONICAL="POST\n/partikulier/v1/automation-event\n${TIMESTAMP}\n${PAYLOAD}"
SIGNATURE=$(echo -ne "${CANONICAL}" | openssl dgst -sha256 -hmac "${SECRET_RAW}" -hex | sed 's/^.* //')
run_request() {
    curl -s -X POST "${BASE_URL}${ENDPOINT}" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: ${SECRET_B64}" \
        -H "X-Partikulier-Key-Id: ${KEY_ID}" \
        -H "X-Partikulier-Timestamp: ${TIMESTAMP}" \
        -H "X-Partikulier-Signature: sha256=${SIGNATURE}" \
        -d "${PAYLOAD}"
}
echo "════════ Test HMAC HTTP Senior ════════"
echo "Event ID: ${EVENT_ID}"
echo "Round 1 (Concurrent 2x)..."
RES1=$(run_request & run_request & wait)
echo "Responses: ${RES1}"
COUNT_OK=$(echo "$RES1" | grep -o '"accepted":true' | wc -l)
COUNT_DUP=$(echo "$RES1" | grep -o '"duplicate":true' | wc -l)
if [ "$COUNT_OK" -eq 2 ] && [ "$COUNT_DUP" -ge 1 ]; then
    echo "[PASS] Idempotence et Concurrence OK"
    exit 0
else
    echo "[FAIL] Échec de la preuve HTTP (OK=$COUNT_OK, DUP=$COUNT_DUP)"
    exit 1
fi
