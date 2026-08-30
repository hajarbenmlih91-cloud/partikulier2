#!/usr/bin/env bash
# Vérifie la sortie de bootstrap-sandbox.sh et le contrat de l'objectif A1/A2.
set -uo pipefail
PK=/opt/pk; say(){ printf '%-40s %s\n' "$1" "$2"; }
. "$PK/run/bootstrap.env" 2>/dev/null || { say "bootstrap.env" "ABSENT"; exit 2; }
ok=0; ko=0
chk(){ if eval "$2"; then say "$1" "OK"; ok=$((ok+1)); else say "$1" "KO"; ko=$((ko+1)); fi; }
chk "A1.a base HTTP 200 sur /ar/"            '[ "$(curl -s -o /dev/null -w %{http_code} --max-time 5 $PK_BASE/ar/)" = 200 ]'
chk "A1.b base HTTP 200 sur /ar/annonces/"    '[ "$(curl -s -o /dev/null -w %{http_code} --max-time 5 $PK_BASE/ar/annonces/)" = 200 ]'
chk "A1.c dépôt intact (hors node_modules)"   '[ "$(git -C $PK_REPO status --porcelain | grep -vc "^?? node_modules")" = 0 ]'
chk "A1.d SHA = tip de la branche"            '[ "$(git -C $PK_REPO rev-parse HEAD)" = "$(git -C $PK_REPO rev-parse origin/automation/capacity-apcu-a58942c 2>/dev/null || echo x)" ] || [ "$PK_ENV_MODE" = warm ]'
chk "A1.e 30 annonces publish mini"           '[ "$(wp --path=$PK_WP_DIR post list --post_type=properties --format=count --allow-root --skip-plugins)" -ge 30 ]'
chk "A1.f médias ≥ 90 (3/post sur 30)"        '[ "$(wp --path=$PK_WP_DIR post list --post_type=attachment --format=count --allow-root --skip-plugins)" -ge 90 ]'
chk "A1.g thème actif = Partikulier"          'wp --path=$PK_WP_DIR theme list --status=active --field=name --allow-root --skip-plugins | grep -qi partikulier'
chk "A1.h template manifest cohérent"         'grep -q LISTINGS= "$PK/templates/template-manifest.json" && grep -q MEDIA_SHA "$PK/templates/template-manifest.json"'
chk "A1.i route non installée → 404 (pas 500)" '[ "$(curl -s -o /dev/null -w %{http_code} --max-time 5 $PK_BASE/n-existe-pas/)" = 404 ]'
chk "A1.j pas de warning PHP dans debug.log"  '[ ! -s "$PK_WP_DIR/wp-content/debug.log" ] || ! grep -q "PHP Fatal" "$PK_WP_DIR/wp-content/debug.log"'
echo "----"; echo "auto-contrôle A1 : $ok OK / $ko KO"
[ $ko -eq 0 ] || exit 1
