#!/bin/bash
# Test de concurrence HMAC Senior v6.17.6
# Exécute 5 rounds de 2 requêtes simultanées avec le même event_id.

WP_DIR=${PK_WP_DIR:-/home/ubuntu/wp-6172-final}
BASE_URL=${PK_BASE:-http://localhost:8094}
SCRIPT_DIR=$(dirname "$0")

echo "--- Démarrage du test de concurrence HMAC (5 rounds) ---"

for i in {1..5}
do
    EVENT_ID="concurrent-event-$i-$(date +%s)"
    echo "Round $i - Event ID: $EVENT_ID"
    
    # Lancement de deux requêtes en parallèle
    php "$SCRIPT_DIR/test-hmac-senior-internal.php" "$EVENT_ID" > "/tmp/res1-$i.json" &
    php "$SCRIPT_DIR/test-hmac-senior-internal.php" "$EVENT_ID" > "/tmp/res2-$i.json" &
    
    wait
    
    # Vérification des résultats
    RES1=$(cat "/tmp/res1-$i.json")
    RES2=$(cat "/tmp/res2-$i.json")
    
    echo "  Appel 1: $RES1"
    echo "  Appel 2: $RES2"
    
    if [[ "$RES1" == *"accepted\":true"* && "$RES2" == *"accepted\":true"* ]]; then
        if [[ ("$RES1" == *"duplicate\":false"* && "$RES2" == *"duplicate\":true"*) || ("$RES1" == *"duplicate\":true"* && "$RES2" == *"duplicate\":false"*) ]]; then
            echo "  Resultat: PASS ✅"
        else
            echo "  Resultat: FAIL ❌ (Problème d'idempotence)"
            exit 1
        fi
    else
        echo "  Resultat: FAIL ❌ (Erreur HTTP/Auth)"
        exit 1
    fi
done

echo "--- Test de concurrence terminé avec succès (5/5 rounds PASS) ---"
