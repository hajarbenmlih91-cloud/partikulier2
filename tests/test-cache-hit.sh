#!/bin/bash
# Test de non-régression du cache de page fichier Partikulier (G2)
# Vérifie qu'une première requête produit MISS et la seconde HIT.

set -eu

URL="${PK_BASE:-http://localhost:8090}/fr/annonces/"
CACHE_DIR="${PK_WP_DIR:-$PWD/wp}/wp-content/uploads/partikulier-cache"

echo "=== Test de non-régression du Cache (P1/G2) ==="

# Vérification préliminaire de l'accessibilité du serveur HTTP
if ! curl -s --connect-timeout 2 "$URL" >/dev/null 2>&1; then
    echo "   Serveur HTTP indisponible ($URL) : test dynamique ignoré (OK statique)"
    exit 0
fi

# Purge préalable du cache
if [ -d "$CACHE_DIR" ]; then
    rm -f "$CACHE_DIR"/*.html "$CACHE_DIR"/*.gz "$CACHE_DIR"/*.br 2>/dev/null || true
fi

# Première requête (MISS)
RESP1=$(curl -s -D - -H "Cookie: pll_language=fr" "$URL")
if echo "$RESP1" | grep -q "X-Partikulier-Cache: MISS"; then
    echo "   Requête 1: MISS (OK)"
else
    echo "❌ Requête 1: Attendu MISS mais reçu :"
    echo "$RESP1" | grep "X-Partikulier-Cache" || echo "Aucun en-tête X-Partikulier-Cache"
    exit 1
fi

# Seconde requête (HIT)
RESP2=$(curl -s -D - -H "Cookie: pll_language=fr" "$URL")
if echo "$RESP2" | grep -q "X-Partikulier-Cache: HIT"; then
    echo "   Requête 2: HIT (OK)"
else
    echo "❌ Requête 2: Attendu HIT mais reçu :"
    echo "$RESP2" | grep "X-Partikulier-Cache" || echo "Aucun en-tête X-Partikulier-Cache"
    exit 1
fi

echo "   Test du cache HTML : HIT fonctionnel         OK"
exit 0
