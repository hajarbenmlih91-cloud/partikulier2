#!/bin/bash
# Test de concurrence HMAC HTTP réel pour Partikulier v6.17.8

BASE_URL=${PK_BASE:-"http://localhost:8097"}
ENDPOINT="/wp-json/partikulier/v1/automation-event"
# Secret Base64 stocké en DB
SECRET_B64="cmVhbC1zZWNyZXQtYnJ1dC0xMjM="
# Secret brut pour la signature (décodé)
SECRET_RAW="real-secret-brut-123"
KEY_ID="real-key-1"
EVENT_ID="n8n-concurrent-$(date +%s)"
TIMESTAMP=$(date +%s)

CANONICAL="POST\n/partikulier/v1/automation-event\n${TIMESTAMP}\n{\"source\":\"n8n\",\"event_type\":\"property_sync\",\"event_id\":\"${EVENT_ID}\"}"
SIGNATURE=$(echo -ne "${CANONICAL}" | openssl dgst -sha256 -hmac "${SECRET_RAW}" -hex | sed 's/^.* //')

run_request() {
    curl -s -X POST "${BASE_URL}${ENDPOINT}" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: ${SECRET_B64}" \
        -H "X-Partikulier-Key-Id: ${KEY_ID}" \
        -H "X-Partikulier-Timestamp: ${TIMESTAMP}" \
        -H "X-Partikulier-Signature: sha256=${SIGNATURE}" \
        -d "{\"source\":\"n8n\",\"event_type\":\"property_sync\",\"event_id\":\"${EVENT_ID}\"}"
}

echo "Lancement de 2 requêtes concurrentes..."
run_request &
run_request &
wait
echo -e "\nTest terminé."
