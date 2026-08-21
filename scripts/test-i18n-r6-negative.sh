#!/usr/bin/env bash
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/theme/templates/parts" "$TMP/scripts"
cp "$ROOT/theme/templates/parts/card-property.php" "$TMP/theme/templates/parts/card-property.php"
cp "$ROOT/scripts/check-i18n-hardcoded.sh" "$TMP/scripts/check-i18n-hardcoded.sh"
cp "$ROOT/scripts/i18n-exceptions.txt" "$TMP/scripts/i18n-exceptions.txt"
printf '\n<?php echo "3 chambres ou plus";\n' >> "$TMP/theme/templates/parts/card-property.php"
chmod +x "$TMP/scripts/check-i18n-hardcoded.sh"
if PK_R6_ROOT="$TMP" "$TMP/scripts/check-i18n-hardcoded.sh" >/tmp/partikulier-r6-negative.out 2>&1; then
  echo "R6 négatif : ECHEC, l’injection n’a pas été détectée"
  cat /tmp/partikulier-r6-negative.out
  rm -f /tmp/partikulier-r6-negative.out
  exit 1
fi
rm -f /tmp/partikulier-r6-negative.out
echo "R6 négatif : PASS, l’injection a été rejetée"
