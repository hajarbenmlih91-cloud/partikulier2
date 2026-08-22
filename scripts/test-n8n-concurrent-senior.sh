#!/bin/bash
# Test de concurrence HMAC réelle - 5 rounds, 2 processus parallèles.
# Usage: PK_WP_DIR=/chemin/vers/wp PK_BASE=http://localhost:8092 bash scripts/test-n8n-concurrent-senior.sh

if [ -z "$PK_WP_DIR" ]; then echo "PK_WP_DIR manquant"; exit 1; fi

echo "--- Démarrage du test de concurrence HMAC Senior ---"

for i in {1..5}; do
    echo "Round $i..."
    # Obtenir les paramètres du round
    VARS=$(PK_WP_DIR=$PK_WP_DIR PK_BASE=$PK_BASE php scripts/test-hmac-real-route.php)
    BASE=$(echo "$VARS" | grep "BASE=" | cut -d= -f2)
    ROUTE=$(echo "$VARS" | grep "ROUTE=" | cut -d= -f2)
    SIG=$(echo "$VARS" | grep "SIGNATURE=" | cut -d= -f2)
    TS=$(echo "$VARS" | grep "TIMESTAMP=" | cut -d= -f2)
    KID=$(echo "$VARS" | grep "KEY_ID=" | cut -d= -f2)
    BODY=$(echo "$VARS" | grep "BODY=" | cut -d= -f2)
    TOKEN=$(echo "$VARS" | grep "AUTH_TOKEN=" | cut -d= -f2)

    URL="${BASE}${ROUTE}"

    # Lancer deux requêtes en parallèle avec le header direct
    curl -s -X POST "$URL" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: $TOKEN" \
        -H "X-Partikulier-Key-Id: $KID" \
        -H "X-Partikulier-Timestamp: $TS" \
        -H "X-Partikulier-Signature: $SIG" \
        -d "$BODY" > /tmp/res1.json &
    
    curl -s -X POST "$URL" \
        -H "Content-Type: application/json" \
        -H "X-Partikulier-Automation: $TOKEN" \
        -H "X-Partikulier-Key-Id: $KID" \
        -H "X-Partikulier-Timestamp: $TS" \
        -H "X-Partikulier-Signature: $SIG" \
        -d "$BODY" > /tmp/res2.json &
    
    wait
    
    R1=$(cat /tmp/res1.json)
    R2=$(cat /tmp/res2.json)
    
    echo "  P1: $R1"
    echo "  P2: $R2"
    
    # Vérifier l'idempotence : l'un doit être duplicate:false, l'autre duplicate:true
    if [[ "$R1" == *"duplicate\":false"* && "$R2" == *"duplicate\":true"* ]] || \
       [[ "$R1" == *"duplicate\":true"* && "$R2" == *"duplicate\":false"* ]]; then
        echo "  Round $i: PASS (Idempotence OK)"
    else
        echo "  Round $i: FAIL (Condition d'idempotence non remplie)"
        exit 1
    fi
done

echo "--- Test de concurrence HMAC Senior: TOUT EST VERT ---"
