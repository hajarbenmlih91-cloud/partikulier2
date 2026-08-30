#!/usr/bin/env bash
# Verificateur des 8 « bugs » revendiques par le rapport dev, rejoue a l'identique
# sur l'environnement reel courant. Sortie = une ligne par affirmation + verdict.
set -uo pipefail
B="${PK_BASE:-http://localhost:8090}"; W="${PK_WP_DIR:-/opt/pk/wp}"
CACHE="$W/wp-content/uploads/partikulier-cache"
WP="WP_CLI_ALLOW_ROOT=1 wp --path=$W --allow-root"
ok(){ printf '%-46s %s\n' "$1" "$2"; }

echo "=== environnement ==="
printf '  SHA theme  : %s\n' "$(git -C /opt/pk/repo rev-parse --short HEAD)"
printf '  Version    : %s\n' "$(grep -m1 'Version:' /opt/pk/repo/theme/style.css | tr -d ' \r')"
printf '  annonces   : %s publiees | posts_per_page=%s\n' \
  "$($WP post list --post_type=properties --post_status=publish --format=count 2>/dev/null|tail -1)" \
  "$($WP option get posts_per_page 2>/dev/null|tail -1)"

echo; echo "=== BUG #1 (cache jamais) ==="
rm -rf "$CACHE"/* 2>/dev/null
h1=$(curl -s -o /dev/null -D - "$B/annonces/" | grep -ic "x-partikulier")
h2=$(curl -s -o /dev/null -D - "$B/annonces/" | grep -ic "x-partikulier")
n=$(ls "$CACHE"/*.html 2>/dev/null | wc -l)
ok "  /annonces/ sans cookie de langue" "en-tete cache sur req1=$h1 req2=$h2 fichiers=$n"
c1=$(curl -s -o /dev/null -H "Cookie: pll_language=fr" -D - "$B/annonces/" | grep -i "x-partikulier" | tr -d '\r')
c2=$(curl -s -o /dev/null -H "Cookie: pll_language=fr" -D - "$B/annonces/" | grep -i "x-partikulier" | tr -d '\r')
ok "  /annonces/ avec cookie deja pose" "req1=[$c1] req2=[$c2] fichiers=$(ls $CACHE/*.html 2>/dev/null|wc -l)"
s=$($WP post list --post_type=properties --field=guid --number=1 2>/dev/null | tail -1 | sed "s#$B##")
u="$B${s#/annonces/}"; u="$B/annonces${s#/annonces}"
x1=$(curl -s -o /dev/null -D - "$u" | grep -i "x-partikulier" | tr -d '\r')
x2=$(curl -s -o /dev/null -D - "$u" | grep -i "x-partikulier" | tr -d '\r')
body2=$(curl -s "$u" | wc -c)
ok "  single $(basename "$u")" "1=[$x1] 2=[$x2] corps_2e_appel=${body2}o"
printf '  fichiers cache: '; for f in "$CACHE"/*.html; do [ -e "$f" ] && printf '%s(%so) ' "$(basename "$f")" "$(stat -c%s "$f")"; done; echo
ttfb=$(for i in 1 2 3 4 5 6 7 8 9 10; do curl -s -o /dev/null -w '%{time_total} ' "$B/annonces/"; done)
ok "  TTFB /annonces/ (10 mesures)" "$ttfb"

echo; echo "=== BUG #2 (pagination) ==="
for p in 1 2 3 4; do printf '  /annonces/page/%s -> %s | /fr/annonces/page/%s -> %s\n' "$p" \
  "$(curl -s -o /dev/null -w '%{http_code}' "$B/annonces/page/$p/")" "$p" "$(curl -s -o /dev/null -w '%{http_code}' "$B/fr/annonces/page/$p/")"; done
printf '  /annonces/?paged=2 -> %s\n' "$(curl -s -o /dev/null -w '%{http_code}' "$B/annonces/?paged=2")"
printf '  liens de pagination dans le HTML : %s\n' "$(curl -s "$B/annonces/" | grep -oE 'href="[^"]*page/[0-9]+' | sort -u | wc -l)"

echo; echo "=== BUG #3 (redirections legacy) ==="
real=$($WP post list --post_type=properties --field=post_name --allow-root --number=1 2>/dev/null | tail -1)
for u in "/property/" "/property/$real/" "/property/mon-bien/" "/property/appartement-lumineux/"; do
  printf '  %-34s -> %s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' "$B$u")" "$(curl -s -o /dev/null -w '%{redirect_url}' "$B$u")"; done

echo; echo "=== BUG #4 (AVIF) ==="
printf '  Imagick charge : %s | GD imageavif : %s\n' "$(php -r 'echo extension_loaded("imagick")?"OUI":"NON";')" "$(php -r 'echo function_exists("imageavif")?"OUI":"NON";')"
printf '  wp_image_editor_supports(avif) : %s\n' "$($WP eval 'echo var_export(wp_image_editor_supports(array("mime_type"=>"image/avif")),true);' 2>/dev/null|tail -1)"
jpg=$(find "$W/wp-content/uploads" -name "*.jpg" ! -name "*.avif" | head -1)
printf '  .avif a cote de %s : %s\n' "$(basename "$jpg")" "$(ls "$jpg.avif" 2>/dev/null && stat -c'%s o' "$jpg.avif" || echo ABSENT)"
printf '  binaries : /usr/bin/avifenc=%s /usr/bin/vips=%s\n' "$(ls /usr/bin/avifenc 2>/dev/null >/dev/null && echo present || echo absent)" "$(ls /usr/bin/vips 2>/dev/null >/dev/null && echo present || echo absent)"
printf '  avertissement admin si echec : %s occurrence(s) de admin_notices/error_log dans class-avif.php\n' "$(grep -c 'admin_notices\|error_log' /opt/pk/repo/theme/inc/class-avif.php)"

echo; echo "=== BUG #5 (notices Estatik) ==="
printf '  debug.log : %s lignes, %s mentions es-framework\n' "$(wc -l < "$W/wp-content/debug.log" 2>/dev/null || echo 0)" "$(grep -c 'es-framework' "$W/wp-content/debug.log" 2>/dev/null || echo 0)"

echo; echo "=== BUG #6 + #7 (JSON-LD et title) ==="
for u in /annonces/ /en/annonces/ /ar/annonces/; do
  t=$(curl -s "$B$u" | grep -oE '<title>[^<]*</title>' | head -1)
  n=$(curl -s "$B$u" | python3 -c "
import sys,re,json
h=sys.stdin.read(); out=[]
for m in re.finditer(r'<script type=\"application/ld\+json\">(.*?)</script>',h,re.S):
    try: d=json.loads(m.group(1))
    except Exception: continue
    for it in (d.get('@graph',[d]) if isinstance(d,dict) else d):
        if it.get('@type')=='ItemList': out.append(str(it.get('numberOfItems')))
print(','.join(out) or 'aucun')")
  printf '  %-14s title=%-40s ItemList.numberOfItems=%s\n' "$u" "${t:0:38}" "$n"
done
echo; echo "=== #8 (metadonnees) ==="
printf '  style.css  : %s\n' "$(grep -E 'Requires at least|Requires PHP|License:' /opt/pk/repo/theme/style.css|tr '\n' ' ')"
printf '  readme.txt : %s\n' "$(grep -E 'Requires at least|Requires PHP|License:' /opt/pk/repo/theme/readme.txt|tr '\n' ' ')"
echo; echo "=== CSP (recommandation 12) ==="
curl -s -o /dev/null -D - "$B/annonces/" | grep -i content-security | tr -d '\r' | grep -o "unsafe-inline" | wc -l | xargs -I{} echo "  occurrences unsafe-inline: {}"
echo; echo "=== these du dev sur #1 : le blocage vient-il du cookie pll_language ? ==="
grep -q "pll_language" /opt/pk/repo/theme/inc/class-cache.php && echo "  OUI, l'exemption existe deja dans class-cache.php (donc la cause n'est plus le cookie sur cette version)" || echo "  NON, pas d'exemption trouvee dans class-cache.php (le blocage par cookie est donc possible sur cette version)"
