#!/bin/bash
# Fabrique l'archive livrable du thème.
#   bash scripts/package.sh 6.14.0
#
# Aligne les 4 fichiers de version, contrôle la qualité, puis produit le zip.
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
T="$ROOT/theme"
V="${1:-}"

if [ -z "$V" ]; then
  echo "Usage : bash scripts/package.sh <version>   (ex. 6.14.0)"
  exit 1
fi

echo "── Alignement des versions sur $V"
sed -i -E "s/^[[:space:]]*Version: *[0-9.]+/ Version: $V/"                 "$T/style.css"
sed -i -E "s/(PARTIKULIER_VERSION', *')[0-9.]+/\1$V/"                  "$T/functions.php"
sed -i -E "s/(\"version\": *\")[0-9.]+/\1$V/"                          "$T/package.json"
sed -i -E "s/^Stable tag: *[0-9.]+/Stable tag: $V/"                    "$T/readme.txt"

echo "── Contrôle qualité"
bash "$ROOT/scripts/check.sh" || { echo "Contrôle échoué : archive non produite."; exit 1; }

OUT="$ROOT/partikulier-$V.zip"
rm -f "$OUT"
( cd "$T" && zip -rq "$OUT" . -x '.git/*' 'node_modules/*' '.DS_Store' )

unzip -t "$OUT" >/dev/null 2>&1 || { echo "Archive corrompue."; exit 1; }

echo
echo "Archive : $OUT"
echo "Taille  : $(ls -lh "$OUT" | awk '{print $5}')"
echo "SHA-256 : $(sha256sum "$OUT" | cut -d' ' -f1)"
echo
echo "Pensez à ajouter l'entrée de changelog dans theme/readme.txt."
