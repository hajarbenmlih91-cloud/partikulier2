#!/usr/bin/env bash
# Matrice : (v6.17.8 | v6.17.22) x (php-S | nginx+php-fpm | Apache+mod_php) x 4 classes d'URL
set -uo pipefail
W=/opt/pk/wp; C=$W/wp-content/uploads/partikulier-cache
L=$W/wp-content/themes/partikulier/inc/class-cache.php
S=${SLUG:-appartement-t2-vue-mer-avec-balcon}

setguard(){ # $1 = on|off
  python3 - "$1" "$L" <<'PY'
import sys
mode, p = sys.argv[1], sys.argv[2]
a = "if ( is_admin_bar_showing() || http_response_code() >= 400 || self::response_sets_cookie() || '' === trim( (string) $html ) ) {"
b = "if ( is_admin_bar_showing() || http_response_code() >= 400 || self::response_sets_cookie() ) {"
s = open(p).read()
if mode == 'off':
    assert a in s, "garde deja absent"
    open(p, 'w').write(s.replace(a, b, 1)); print("     garde trim RETIRE (simulation v6.17.8)")
else:
    assert b in s, "garde deja present"
    open(p, 'w').write(s.replace(b, a, 1)); print("     garde trim REMIS (v6.17.22)")
PY
}

t(){ # $1 etat, $2 port, $3 url, $4 keep(1=pas de purge)
  [ "${4:-0}" = 1 ] || { rm -rf "$C"/* 2>/dev/null; sleep .3; }
  local a b fa fb nf
  a=$(curl -s -o /dev/null -w '%{http_code}/%{size_download}' -D /tmp/ha -H "Host: localhost:$2" -H "Cookie: pll_language=fr" --max-time 40 "http://127.0.0.1:$2$3")
  fa=$(tr -d '\r' < /tmp/ha | grep -io 'x-partikulier-cache: [a-z]*' | awk '{print $2}' | tr 'a-z' 'A-Z')
  b=$(curl -s -o /dev/null -w '%{http_code}/%{size_download}' -D /tmp/hb -H "Host: localhost:$2" -H "Cookie: pll_language=fr" --max-time 40 "http://127.0.0.1:$2$3")
  fb=$(tr -d '\r' < /tmp/hb | grep -io 'x-partikulier-cache: [a-z]*' | awk '{print $2}' | tr 'a-z' 'A-Z')
  nf=$(find "$C" -maxdepth 1 -name '*.html' -printf '%so ' 2>/dev/null | tr -s ' ')
  printf '  %-10s :%-5s %-30s 1er=%-11s[%-4s] 2e=%-11s[%-4s] cache_fichiers=%s\n' "$1" "$2" "$3" "$a" "${fa:--}" "$b" "${fb:--}" "${nf:--}"
}

echo "--- etat A : garde trim retire (code equivalent v6.17.8) ---"; setguard off
for port in 8090 8091 8092; do
  for url in "/annonces/" "/annonces/$S/" "/annonce/marrakech/$S/" "/annonces/slug-inexistant-xyz/"; do
    t "v6.17.8" "$port" "$url"
  done
done
echo; echo "--- remise du garde ---"; setguard on
for port in 8090 8091 8092; do
  for url in "/annonces/" "/annonces/$S/" "/annonce/marrakech/$S/" "/annonces/slug-inexistant-xyz/"; do
    t "v6.17.22" "$port" "$url"
  done
done

echo; echo "--- URLs de campagne (le point P5 du CDC, mesure en reel) ---"
setguard off   # on est sur le code 6.17.8-equivalent : le cache marche, seul trim manque
for u in "/annonces/" "/annonces/?fbclid=IwAR0abc" "/annonces/?utm_source=fb&utm_campaign=lancement" "/fr/annonces/?pk_order=price"; do
  t "campagne" 8090 "$u" 1
done
setguard on
