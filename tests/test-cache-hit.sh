#!/bin/bash
# Test de non-régression du cache de page fichier Partikulier
# Vérifie qu'une première requête produit MISS et la seconde HIT.

set -eu

URL="${PK_BASE:-http://localhost:8090}/fr/annonces/"

echo "=== Test de non-régression du Cache (P1) ==="

# Purge préalable du cache
rm -rf wp/wp-content/uploads/partikulier-cache/* 2>/dev/null || true

# Première requête (MISS)
RESP1=$(curl -s -D - -H "Cookie: pll_language=fr" "$URL")
if echo "$RESP1" | grep -q "X-Partikulier-Cache: MISS"; then
    echo "✅ Requête 1: MISS (OK)"
else
    echo "❌ Requête 1: Attendu MISS mais reçu :"
    echo "$RESP1" | grep "X-Partikulier-Cache" || echo "Aucun en-tête X-Partikulier-Cache"
    exit 1
fi

# Seconde requête (HIT)
RESP2=$(curl -s -D - -H "Cookie: pll_language=fr" "$URL")
if echo "$RESP2" | grep -q "X-Partikulier-Cache: HIT"; then
    echo "✅ Requête 2: HIT (OK)"
else
    echo "❌ Requête 2: Attendu HIT mais reçu :"
    echo "$RESP2" | grep "X-Partikulier-Cache" || echo "Aucun en-tête X-Partikulier-Cache"
    exit 1
fi

echo "=== TEST CACHE REUSSI (PASS) ==="
exit 0
