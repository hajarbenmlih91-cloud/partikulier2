#!/bin/bash
# Contrôle qualité avant livraison.
#   bash scripts/check.sh
#
# Vérifie : syntaxe PHP, syntaxe JS, cohérence des numéros de version,
# et absence de régressions connues.
set -Eeuo pipefail
ROOT="${PK_CHECK_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
T="$ROOT/theme"
FAIL=0

echo "════════ Contrôle qualité Partikulier ════════"
echo

# --------------------------------------------------------------- syntaxe PHP
echo "── Syntaxe PHP"
if command -v php >/dev/null 2>&1; then
  n=0; bad=0
  while IFS= read -r f; do
    n=$((n+1))
    php -l "$f" >/dev/null 2>&1 || { echo "   ERREUR  $f"; php -l "$f" 2>&1 | head -2; bad=1; }
  done < <(find "$T" -name '*.php' -not -path '*/.git/*')
  [ $bad -eq 0 ] && echo "   $n fichiers, aucune erreur" || FAIL=1
else
  echo "   php absent : contrôle ignoré (installez php-cli)"
fi

# ---------------------------------------------------------------- syntaxe JS
echo "── Syntaxe JavaScript"
if command -v node >/dev/null 2>&1; then
  bad=0
  js_count=0
  for f in "$T"/assets/js/*.js "$ROOT"/scripts/*.mjs; do
    [ -f "$f" ] || continue
    js_count=$((js_count + 1))
    node --check "$f" >/dev/null 2>&1 || { echo "   ERREUR  $f"; bad=1; }
  done
  [ $bad -eq 0 ] && echo "   $js_count fichiers, aucune erreur" || FAIL=1
else
  echo "   node absent : contrôle ignoré"
fi

# ------------------------------------------------------------------ versions
echo "── Cohérence des versions"
v_css=$(grep -m1 -oE 'Version: *[0-9.]+' "$T/style.css" | grep -oE '[0-9.]+')
v_php=$(grep -m1 -oE "PARTIKULIER_VERSION', *'[0-9.]+" "$T/functions.php" | grep -oE '[0-9.]+$')
v_pkg=$(grep -m1 -oE '"version": *"[0-9.]+"' "$T/package.json" | grep -oE '[0-9.]+')
v_rme=$(grep -m1 -oE 'Stable tag: *[0-9.]+' "$T/readme.txt" | grep -oE '[0-9.]+')
echo "   style.css $v_css · functions.php $v_php · package.json $v_pkg · readme.txt $v_rme"
if [ "$v_css" = "$v_php" ] && [ "$v_css" = "$v_pkg" ] && [ "$v_css" = "$v_rme" ]; then
  echo "   Les 4 fichiers concordent"
else
  echo "   INCOHÉRENCE : alignez les 4 fichiers avant de livrer"; FAIL=1
fi

# ------------------------------------------------ garde-fou routes automation
 echo "── Garde-fou routes automation"
 automation_bad=0
 for f in "$T"/inc/class-buyer-qualification.php "$T"/inc/class-lead-retention.php "$T"/inc/class-listing-approval.php; do
   [ -f "$f" ] || continue
   if grep -q "register_rest_route" "$f"; then
     echo "   ROUTE AUTOMATION DIRECTE INTERDITE : $f"
     automation_bad=1
   fi
 done
 if [ "$automation_bad" -eq 0 ]; then
   echo "   modules automation via wrapper central       OK"
 else
   FAIL=1
 fi

# ------------------------------------------------ garde-fou R6 i18n
 echo "── Garde-fou R6 chaînes publiques"
 if [ -x "$ROOT/scripts/check-i18n-hardcoded.sh" ] && [ -f "$ROOT/scripts/i18n-exceptions.txt" ]; then
   "$ROOT/scripts/check-i18n-hardcoded.sh" || FAIL=1
 else
   echo "   script R6 ou exceptions absent"; FAIL=1
 fi

# ------------------------------------------- test de non-régression du cache (CDC G2)
 echo "── Cache de pages (test dynamique ; exige PK_WP_DIR + serveur)"
 if [ -x "$ROOT/tests/test-cache-hit.sh" ] || [ -f "$ROOT/tests/test-cache-hit.sh" ]; then
   bash "$ROOT/tests/test-cache-hit.sh" || FAIL=1
 else
   echo "   tests/test-cache-hit.sh absent" >&2; FAIL=1
 fi

# ------------------------------------------------------- régressions connues
 echo "── Régressions connues"

# Le cœur des favoris doit avoir un style, sinon il ne rougit pas.
if grep -q "pk-wish-active" "$T/assets/css/style.css"; then
  echo "   .pk-wish-active stylée               OK"
else
  echo "   .pk-wish-active SANS STYLE : le cœur ne deviendra pas rouge"; FAIL=1
fi

# Un seul gestionnaire de clic sur le cœur (deux s'annulent).
h=$(grep -c 'b\.addEventListener("click"' "$T/assets/js/main.js" 2>/dev/null || true)
h=${h:-0}
echo "   gestionnaires favoris : $h gestionnaire(s) addEventListener détecté(s)"
if [ "$h" -eq 1 ]; then
  echo "   cardinalité du gestionnaire favoris         OK"
else
  echo "   cardinalité invalide : attendu 1, trouvé $h"; FAIL=1
fi

# main.js doit se neutraliser sur la page de dépôt.
if grep -q "pk-steps" "$T/assets/js/main.js"; then
  echo "   garde .pk-steps dans main.js          OK"
else
  echo "   garde .pk-steps ABSENTE : conflit probable sur l'upload de photos"; FAIL=1
fi

# Vocabulaire interdit par le client, dans les textes VISIBLES uniquement.
# Une occurrence dans une liste de rejet (on refuse l'ancien rôle) est légitime :
# on ne signale donc que les chaînes traduites ou affichées.
vis=$(grep -rn "mandataire" "$T" --include='*.php' --include='*.js' --include='*.css' 2>/dev/null \
      | grep -iE "__\(|_e\(|esc_html|esc_attr|>.*mandataire.*<" || true)
if [ -n "$vis" ]; then
  echo "   MOT INTERDIT « mandataire » visible par l'utilisateur :"
  echo "$vis" | head -3 | sed 's/^/      /'
  FAIL=1
else
  echo "   vocabulaire (pas de « mandataire » affiché)  OK"
fi

echo
if [ $FAIL -eq 0 ]; then
  echo "════════ Tout est conforme ════════"
else
  echo "════════ Corrections nécessaires ════════"
fi
exit $FAIL
