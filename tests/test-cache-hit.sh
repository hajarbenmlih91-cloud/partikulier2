#!/usr/bin/env bash
# Test de non-régression du cache de pages fichier Partikulier (CDC v5, porte G2).
#
# Ce test est exigeant par construction :
#   - PK_WP_DIR est obligatoire (sans contrôle du dossier de cache, il ne prouve rien) ;
#   - un serveur injoignable est un ECHEC, pas une neutralisation (un test qui ne peut
#     pas échouer n'est pas un test) ;
#   - HIT interdit au 1er tour après purge ; HIT exigé aux 2e et 3e ;
#   - corps du HIT > 4 000 o (un HIT quasi vide = page blanche, défaut mesuré sur v6.17.8) ;
#   - zéro fichier de cache de 0 octet ;
#   - sur chaque échec, un bloc « preuve » est collé dans le log : dossier, permissions,
#     upload_basedir, home_url, hôte autorisé pour la clé, écart d'horloge entre
#     time() et filemtime(), presence de Set-Cookie/Location dans les réponses.
#     Un FAIL de cette gate doit être auto-diagnostique, sinon on rejoue à l'aveugle.
#
#   bash tests/test-cache-hit.sh                # contre un serveur
#   bash tests/test-cache-hit.sh --self-check    # exige que le test ROUGISSE sur un code cassé
set -uo pipefail

ROOT=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
BASE=${PK_BASE:-http://localhost:8090}
WP=${PK_WP_DIR:-}
URL="$BASE/fr/annonces/"
LANG_COOKIE=${PK_KIT_COOKIE:-pll_language=fr}
fail=0
say(){ printf '%s\n' "$*"; }
err(){ printf '  ECHEC %s\n' "$*" >&2; fail=1; }

[ -n "$WP" ] || { err "PK_WP_DIR doit pointer vers l'install WordPress (sinon le dossier de cache n'est pas controlable). Ex : PK_WP_DIR=\$PWD/.runtime/wp-<version>"; exit 1; }
CACHE_DIR="$WP/wp-content/uploads/partikulier-cache"
[ -d "$WP" ] || { err "PK_WP_DIR inexistant : $WP"; exit 1; }

hdr(){ tr -d '\r' < "$1" 2>/dev/null; }
cache_state(){ hdr "$1" | grep -io '^x-partikulier-cache: [a-z]*' | tail -1 | sed 's/.*: //'; }

dump(){ # preuve collée dans le log CI
  local f now lag
  say "  — preuve —"
  say "    PK_WP_DIR=$WP  PK_BASE=$BASE"
  say "    upload_basedir=$(wp --path="$WP" eval 'echo wp_get_upload_dir()["basedir"];' --allow-root 2>/dev/null | tail -1)"
  say "    home_url=$(wp --path="$WP" option get home --allow-root 2>/dev/null | tail -1)"
  say "    hote_cle_de_cache=$(wp --path="$WP" eval 'echo wp_parse_url(home_url("/"),PHP_URL_HOST);' --allow-root 2>/dev/null | tail -1)"
  say "    dossier=$CACHE_DIR existe=$([ -d "$CACHE_DIR" ] && echo oui || echo NON) perms=$(stat -c '%A %U:%G' "$CACHE_DIR" 2>/dev/null || echo n-a)"
  say "    contenu=$(ls -la "$CACHE_DIR" 2>/dev/null | tail -n +2 | awk '{printf "%s(%so) ",$9,$5}' | cut -c1-200)"
  f=$(ls "$CACHE_DIR"/*.html 2>/dev/null | grep -v '/index.html$' | head -1)
  if [ -n "$f" ]; then now=$(date +%s); lag=$(( now - $(stat -c %Y "$f") )); say "    ecart_horloge_time_mois_du_fichier=${lag}s (un ecart negatif ou un mtime=0 fait purger une entree fraiche)"; else say "    ecart_horloge_time_mois_du_fichier=n/a (aucun .html)"; fi
  say "    set-cookie/au_tour1=$(hdr /tmp/pk-h1 | grep -icE '^(set-cookie|location):')  set-cookie/au_tour2=$(hdr /tmp/pk-h2 | grep -icE '^(set-cookie|location):')"
  say "    codes_http=$(awk 'NR==1{print}' /tmp/pk-h1 2>/dev/null | tr -d '\r') / $(head -1 /tmp/pk-h2 2>/dev/null | tr -d '\r')"
}

# --- auto-vérification : le test doit savoir ROUGIR sur un code volontairement cassé ---
if [ "${1:-}" = "--self-check" ]; then
  TDIR=$(wp --path="$WP" eval 'echo wp_get_theme()->get_stylesheet();' --allow-root 2>/dev/null | tail -1)
  CDIR=$(wp --path="$WP" eval 'echo WP_CONTENT_DIR;' --allow-root 2>/dev/null | tail -1)
  { [ -n "$TDIR" ] && [ -n "$CDIR" ]; } || { err "ne peut pas localiser le thème actif (wp-cli + PK_WP_DIR requis)"; exit 1; }
  SRC="$CDIR/themes/$TDIR/inc/class-cache.php"
  [ -f "$SRC" ] || { err "thème actif sans class-cache.php : $SRC"; exit 1; }
  if grep -q "sanitize_key( wp_unslash( \$_SERVER\['REQUEST_METHOD'\]" "$SRC"; then
    err "le thème actif contient déjà le bug : rien à injecter, auto-vérification inapplicable"; exit 1
  fi
  BK=$(mktemp) && cp "$SRC" "$BK" || { err "sauvegarde impossible"; exit 1; }
  python3 -c '
import sys
p = sys.argv[1]; s = open(p, encoding="utf-8").read()
bug = "if ( \x27GET\x27 !== ( isset( \$_SERVER[\x27REQUEST_METHOD\x27] ) ? sanitize_key( wp_unslash( \$_SERVER[\x27REQUEST_METHOD\x27] ) ) : \x27\x27 ) ) {"
for c in ("\t\tif ( \x27GET\x27 !== $method ) {", "\tif ( \x27GET\x27 !== $method ) {"):
    if c in s:
        open(p, "w", encoding="utf-8").write(s.replace(c, bug, 1)); sys.exit(0)
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
  if [ "$rc" -ne 0 ] && [ "$r" = 0 ]; then say "  [self-check] OK : le test ROUGIT sur le code cassé (rc=$rc), fichier restauré"; exit 0; fi
  err "[self-check] le test PASSE sur un code cassé (rc=$rc) : il est décoratif, il faut le réécrire"
  exit 1
fi

if ! curl -s --connect-timeout 3 -o /dev/null "$URL"; then
  err "serveur injoignable sur $URL : démarrer l'install (scripts/sandbox/bootstrap-sandbox.sh) ou définir PK_BASE — ignorer rendrait le test faux"
  exit 1
fi

say "== test cache : $URL =="
rm -f "$CACHE_DIR"/*.html "$CACHE_DIR"/*.gz "$CACHE_DIR"/*.br 2>/dev/null
c1=$(curl -s -o /tmp/pkt1 -w '%{http_code}' -D /tmp/pk-h1 "$URL" -H "Cookie: $LANG_COOKIE"); s1=$(cache_state /tmp/pk-h1); n1=$(wc -c </tmp/pkt1)
c2=$(curl -s -o /tmp/pkt2 -w '%{http_code}' -D /tmp/pk-h2 "$URL" -H "Cookie: $LANG_COOKIE"); s2=$(cache_state /tmp/pk-h2); n2=$(wc -c </tmp/pkt2)
c3=$(curl -s -o /tmp/pkt3 -w '%{http_code}' -D /tmp/pk-h3 "$URL" -H "Cookie: $LANG_COOKIE"); s3=$(cache_state /tmp/pk-h3)

{ [ "$c1" = 200 ] && [ "$c2" = 200 ] && [ "$c3" = 200 ]; } || { err "codes HTTP inattendus : $c1/$c2/$c3 (attendu 200)"; dump; }
if [ "$s1" = HIT ]; then { err "1re requête après purge = HIT : invalidation inefficace"; dump; }; else say "  1er tour : ${s1:-MISS} (${n1} o)"; fi
if [ "$s2" = HIT ]; then say "  2e tour : HIT (${n2} o)"; else { err "2e tour : ${s2:-AUCUN EN-TÊTE} — le cache ne sert pas"; dump; }; fi
[ "$s3" = HIT ] || { err "3e tour : ${s3:-AUCUN} (attendu HIT stable)"; dump; }
[ "$n2" -gt 4000 ] || { err "corps du HIT = ${n2} o : page blanche servie avec un HIT"; dump; }
if [ -n "$(find "$CACHE_DIR" -maxdepth 1 -name '*.html' ! -name 'index.html' -size 0 -print -quit 2>/dev/null)" ]; then
  err "fichier de cache à 0 octet (empoisonnement)"; dump
else
  e=$(find "$CACHE_DIR" -maxdepth 1 -name '*.html' ! -name 'index.html' 2>/dev/null | wc -l)
  [ "$e" -ge 1 ] || { err "aucune entrée de cache écrite alors qu'un HIT est annoncé"; dump; }
  say "  dossier de cache : $e entrée(s), 0 vide"
fi

if [ "$fail" -eq 0 ]; then say "  RESULTAT: PASS"; else say "  RESULTAT: FAIL"; fi
exit "$fail"
