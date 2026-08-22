#!/usr/bin/env bash
# R6 : bloque les libellés d’interface connus hors gettext dans les gabarits publics.
set -u
ROOT="${PK_R6_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
T="$ROOT/theme"
EXCEPTIONS="$ROOT/scripts/i18n-exceptions.txt"
FAIL=0
PATTERN='Studio|Pièce principale|3 chambres ou plus|3 salons ou plus|3 salles de bains ou plus|Terrasse|France|Prix sur demande|/ mois'
TMP="$(mktemp)"
trap 'rm -f "$TMP"' EXIT
find "$T/templates" "$T/estatik4" -type f -name '*.php' -print0 2>/dev/null \
  | xargs -0 grep -nE "$PATTERN" 2>/dev/null \
  | grep -vE '__\(|_e\(|esc_html|esc_attr|_n\(|_x\(|i18n-exceptions' \
  | grep -vFf "$EXCEPTIONS" > "$TMP" || true
# Independent negative-proof path: detect a literal directly emitted by a public
# template without receiving its value from the test runner.
DIRECT_PATTERN='(^|[;{}[:space:]])echo[[:space:]]*"[^"$]{20,}"'
find "$T/templates" "$T/estatik4" -type f -name '*.php' -print0 2>/dev/null \
  | xargs -0 grep -nE "$DIRECT_PATTERN" 2>/dev/null \
  | grep -vE '__\(|_e\(|esc_html|esc_attr|_n\(|_x\(|i18n-exceptions' \
  | grep -vFf "$EXCEPTIONS" >> "$TMP" || true
if [ -s "$TMP" ]; then
  echo "R6: littéral public non gettext détecté :"
  head -20 "$TMP" | sed 's/^/   /'
  FAIL=1
else
  echo "R6: aucun littéral public interdit hors gettext"
fi
exit "$FAIL"
