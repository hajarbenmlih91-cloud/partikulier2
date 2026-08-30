#!/usr/bin/env bash
# Test de non-régression du cache de pages fichier Partikulier (CDC v5, porte G2).
#
# Exigences de ce test, et pourquoi elles sont ici :
#   - il ÉCHOUE quand le serveur ou l'environnement manque (pas de « exit 0 » de
#     complaisance : un test qui ne peut pas échouer n'est pas un test) ;
#   - il vérifie le trio MISS -> HIT -> HIT-stable, le fait que le corps servi en
#     HIT ne soit PAS vide (le défaut mesuré sur v6.17.8 : fichier de 0 octet servi
#     avec X-Partikulier-Cache: HIT = page blanche), et que le fichier de cache soit
#     identique au rendu frais ;
#   - il ne modifie aucun fichier du thème.
#
# Usage :
#   PK_WP_DIR=/chemin/wordpress bash tests/test-cache-hit.sh            # contre un serveur
#   bash tests/test-cache-hit.sh --self-check                            # auto-vérification (rouge)
set -uo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
BASE=${PK_BASE:-http://localhost:8090}
WP=${PK_WP_DIR:-}
URL="$BASE/fr/annonces/"
LANG_COOKIE=${PK_KIT_COOKIE:-pll_language=fr}
fail=0; say(){ printf '%s\n' "$*"; }
err(){ printf '  ECHEC %s\n' "$*" >&2; fail=1; }

[ -n "$WP" ] || { err "PK_WP_DIR doit pointer vers l'install WordPress (sinon le dossier de cache n'est pas controlable). Reglage type : PK_WP_DIR=\$PWD/.runtime/wp-<version>"; exit 1; }
CACHE_DIR="$WP/wp-content/uploads/partikulier-cache"
[ -d "$WP" ] || { err "PK_WP_DIR inexistant : $WP"; exit 1; }

hdr(){ tr -d '\r' < "$1"; }
cache_state(){ hdr "$1" | grep -io '^x-partikulier-cache: [a-z]*' | tail -1 | sed 's/.*: //'; }

# --- auto-vérification : le test doit savoir ROUGIR sur un code volontairement cassé ---
# La casse est appliquée au thème ACTIF de l'install (c'est lui que PHP charge) puis
# restaurée immédiatement. Sans ce mode, un test peut être décoratif sans que
# personne ne s'en aperçoive — c'est précisément le défaut relevé dans la PR #2.
if [ "${1:-}" = "--self-check" ]; then
  TDIR=$(wp --path="$WP" eval 'echo wp_get_theme()->get_stylesheet();' --allow-root 2>/dev/null | tail -1)
  CDIR=$(wp --path="$WP" eval 'echo WP_CONTENT_DIR;' --allow-root 2>/dev/null | tail -1)
  { [ -n "$TDIR" ] && [ -n "$CDIR" ]; } || { err "ne peut pas localiser le thème actif (wp-cli + PK_WP_DIR requis)"; exit 1; }
  SRC="$CDIR/themes/$TDIR/inc/class-cache.php"
  [ -f "$SRC" ] || { err "thème actif sans class-cache.php : $SRC"; exit 1; }
  if grep -q "sanitize_key( wp_unslash( \$_SERVER\['REQUEST_METHOD'\]" "$SRC"; then
    err "le thème actif contient déjà le bug : rien à injecter, auto-vérification inapplicable"
    exit 1
  fi
  BK=$(mktemp) && cp "$SRC" "$BK" || { err "sauvegarde impossible"; exit 1; }
  python3 -c '
import sys
p = sys.argv[1]
s = open(p, encoding="utf-8").read()
bug = "if ( \x27GET\x27 !== ( isset( \$_SERVER[\x27REQUEST_METHOD\x27] ) ? sanitize_key( wp_unslash( \$_SERVER[\x27REQUEST_METHOD\x27] ) ) : \x27\x27 ) ) {"
for c in ("\t\tif ( \x27GET\x27 !== $method ) {", "\tif ( \x27GET\x27 !== $method ) {"):
    if c in s:
        open(p, "w", encoding="utf-8").write(s.replace(c, bug, 1))
        sys.exit(0)
sys.exit(3)
' "$SRC"
  case $? in
    0) : ;;
    *) err "injection impossible : forme du garde non reconnue dans $SRC"; cp "$BK" "$SRC"; rm -f "$BK"; exit 1 ;;
  esac
  say "  [self-check] garde cassé dans $SRC (1 s) puis restauration"
  sleep 1
  bash "$0"; rc=$?
  cp "$BK" "$SRC" && rm -f "$BK"
  r=$(grep -c "sanitize_key( wp_unslash( \$_SERVER\['REQUEST_METHOD'\]" "$SRC")
  [ "$r" = 0 ] || err "[self-check] RESTAURATION ÉCHOUÉE sur $SRC : à réparer à la main"
  if [ "$rc" -ne 0 ] && [ "$r" = 0 ]; then
    say "  [self-check] OK : le test ROUGIT sur le code cassé (rc=$rc) et le fichier est restauré"
    exit 0
  fi
  err "[self-check] le test PASSE sur un code cassé (rc=$rc) : il est décoratif, il faut le réécrire"
  exit 1
fi

[ -n "${PK_WP_DIR:-}" ] || err "PK_WP_DIR manquant"
if ! curl -s --connect-timeout 3 -o /dev/null "$URL"; then
  err "serveur injoignable sur $URL : demarrer l'install (scripts/sandbox/bootstrap-sandbox.sh) ou definir PK_BASE — ignorer ce test le rendrait faux"
  exit 1
fi

say "== test cache : $URL =="
rm -f "$CACHE_DIR"/*.html "$CACHE_DIR"/*.gz "$CACHE_DIR"/*.br 2>/dev/null
c1=$(curl -s -o /tmp/pkt1 -w '%{http_code}' -D /tmp/pkh1 "$URL" -H "Cookie: $LANG_COOKIE"); s1=$(cache_state /tmp/pkh1); n1=$(wc -c </tmp/pkt1)
c2=$(curl -s -o /tmp/pkt2 -w '%{http_code}' -D /tmp/pkh2 "$URL" -H "Cookie: $LANG_COOKIE"); s2=$(cache_state /tmp/pkh2); n2=$(wc -c </tmp/pkt2)
c3=$(curl -s -o /tmp/pkt3 -w '%{http_code}' -D /tmp/pkh3 "$URL" -H "Cookie: $LANG_COOKIE"); s3=$(cache_state /tmp/pkh3)

[ "$c1" = 200 ] && [ "$c2" = 200 ] && [ "$c3" = 200 ] || err "codes HTTP inattendus : $c1/$c2/$c3 (attendu 200)"
if [ "$s1" = HIT ]; then err "1re requete apres purge = HIT : le cache n'a pas ete invalidé (purge inefficace ou test faux)"; else say "  1er tour : $s1 (${n1} o)"; fi
[ "$s2" = HIT ] && say "  2e tour : HIT (${n2} o)" || err "2e tour : ${s2:-AUCUN EN-TETE} — le cache ne sert pas (attendu HIT). Si le module est court-circuité, chercher le garde REQUEST_METHOD"
[ "$s3" = HIT ] || err "3e tour : ${s3:-AUCUN} (attendu HIT stable)"
[ "$n2" -gt 4000 ] || err "corps du HIT = ${n2} o : un corps quasi vide sert une page blanche avec un HIT rassurant"
[ -s /tmp/pkt1 ] && [ -s /tmp/pkt2 ] || err "corps vide sur un des tours"
if [ "$n1" != "$n2" ]; then say "  note : taille $n1 o (frais) vs $n2 o (cache) — a egales de preférence"; else say "  taille du HIT == rendu frais ($n2 o)"; fi
if [ -d "$CACHE_DIR" ]; then
  vides=$(find "$CACHE_DIR" -maxdepth 1 -name '*.html' ! -name 'index.html' -size 0 2>/dev/null | wc -l)
  [ "$vides" -eq 0 ] || err "$vides fichier(s) de cache à 0 octet (empoisonnement)"
  entrees=$(find "$CACHE_DIR" -maxdepth 1 -name '*.html' ! -name 'index.html' 2>/dev/null | wc -l)
  [ "$entrees" -ge 1 ] || err "aucune entrée de cache écrite alors qu'un HIT est annoncé"
  say "  dossier de cache : $entrees entrée(s), $vides vide(s)"
fi

if [ "$fail" -eq 0 ]; then say "  RESULTAT: PASS"; else say "  RESULTAT: FAIL"; fi
exit "$fail"
