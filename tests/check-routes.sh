#!/bin/bash
# Vérifie le contrat de routes.
#   bash tests/check-routes.sh <base_url>
set -u
BASE_URL="${1:-http://localhost:8097}"
CONTRACT="/home/ubuntu/partikulier2/tests/routes-contract.json"
FAIL=0

echo "════════ Vérification du contrat de routes ════════"
echo "Cible : $BASE_URL"
echo

# Utilise jq pour itérer sur les scénarios
while IFS= read -r scenario; do
    url_path=$(echo "$scenario" | jq -r '.url')
    expected_code=$(echo "$scenario" | jq -r '.expected_code')
    desc=$(echo "$scenario" | jq -r '.description')
    
    url="${BASE_URL}${url_path}"
    
    # Récupère le code HTTP et la location sans suivre les redirections
    response=$(curl -s -o /dev/null -w "%{http_code} %{redirect_url}" "$url")
    code=$(echo "$response" | cut -d' ' -f1)
    location=$(echo "$response" | cut -d' ' -f2)
    
    if [ "$code" -eq "$expected_code" ]; then
        # Vérification supplémentaire pour les redirections
        if [ "$code" -eq 301 ] || [ "$code" -eq 302 ]; then
            expected_loc=$(echo "$scenario" | jq -r '.expected_location // empty')
            expected_loc_contains=$(echo "$scenario" | jq -r '.expected_location_contains // empty')
            
            if [ -n "$expected_loc" ]; then
                # On compare la fin de la location pour gérer le port/domaine
                if [[ "$location" == *"$expected_loc" ]]; then
                    echo "   [PASS] $desc ($url) -> $code OK"
                else
                    echo "   [FAIL] $desc ($url) -> $code mais location $location au lieu de $expected_loc"
                    FAIL=1
                fi
            elif [ -n "$expected_loc_contains" ]; then
                if [[ "$location" == *"$expected_loc_contains"* ]]; then
                    echo "   [PASS] $desc ($url) -> $code OK"
                else
                    echo "   [FAIL] $desc ($url) -> $code mais location $location ne contient pas $expected_loc_contains"
                    FAIL=1
                fi
            else
                echo "   [PASS] $desc ($url) -> $code OK"
            fi
        else
            echo "   [PASS] $desc ($url) -> $code OK"
        fi
    else
        echo "   [FAIL] $desc ($url) -> $code au lieu de $expected_code"
        FAIL=1
    fi
done < <(jq -c '.scenarios[]' "$CONTRACT")

echo
if [ $FAIL -eq 0 ]; then
    echo "════════ Contrat de routes validé ════════"
else
    echo "════════ ÉCHEC du contrat de routes ════════"
fi
exit $FAIL
