#!/bin/bash
# Test de concurrence HMAC Senior v6.17.5.
# Usage: PK_WP_DIR=/path/to/wp PK_BASE=http://localhost:8092 bash scripts/test-n8n-concurrent-senior.sh

echo "--- Démarrage du test de concurrence HMAC Senior (5 rounds) ---"

for i in {1..5}
do
    echo "Round $i..."
    # 1. Préparer les données via le script PHP
    VARS=$(PK_WP_DIR=$PK_WP_DIR PK_BASE=$PK_BASE php scripts/test-hmac-real-route.php)

    BASE=$(echo "$VARS" | grep "BASE=" | cut -d= -f2)
    ROUTE=$(echo "$VARS" | grep "ROUTE=" | cut -d= -f2)
    SIG=$(echo "$VARS" | grep "SIGNATURE=" | cut -d= -f2)
    TS=$(echo "$VARS" | grep "TIMESTAMP=" | cut -d= -f2)
    KID=$(echo "$VARS" | grep "KEY_ID=" | cut -d= -f2)
    BODY=$(echo "$VARS" | grep "BODY=" | cut -d= -f2)
    TOKEN=$(echo "$VARS" | grep "AUTH_TOKEN=" | cut -d= -f2)

    # 2. Lancer 2 requêtes en parallèle
    tmp_res1=$(mktemp)
    tmp_res2=$(mktemp)

    curl -s -X POST "${BASE}${ROUTE}" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: $TOKEN" \
        -H "X-Partikulier-Key-Id: $KID" \
        -H "X-Partikulier-Timestamp: $TS" \
        -H "X-Partikulier-Signature: $SIG" \
        -d "$BODY" > "$tmp_res1" &

    curl -s -X POST "${BASE}${ROUTE}" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: $TOKEN" \
        -H "X-Partikulier-Key-Id: $KID" \
        -H "X-Partikulier-Timestamp: $TS" \
        -H "X-Partikulier-Signature: $SIG" \
        -d "$BODY" > "$tmp_res2" &

    wait

    res1=$(cat "$tmp_res1")
    res2=$(cat "$tmp_res2")
    rm "$tmp_res1" "$tmp_res2"

    echo "P1: $res1"
    echo "P2: $res2"

    if [[ "$res1" == *"duplicate"* ]] && [[ "$res2" == *"duplicate"* ]]; then
        echo "Round $i: PASS"
    else
        echo "Round $i: FAIL"
        exit 1
    fi
done

echo "--- Test HMAC 5/5 PASS ---"
