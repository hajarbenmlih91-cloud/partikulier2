#!/usr/bin/env bash
# Diagnostic de la gate bloquante n°32 : pagination d'archive et ItemList.
# Ce script ne corrige rien : il produit les faits qui restreignent l'hypothese.
set -uo pipefail
B=${PK_BASE:-http://localhost:8090}; W=${PK_WP_DIR:-/opt/pk/wp}
export WP_CLI_ALLOW_ROOT=1
echo "== 1. HTTP =="
for u in /fr/annonces/ /fr/annonces/page/2/ /fr/annonces/page/3/; do
  printf '  %-24s %s cartes=%s\n' "$u" "$(curl -s -o /tmp/diag.html -w '%{http_code}' -H 'Cookie: pll_language=fr' "$B$u")" "$(grep -c pk-card /tmp/diag.html)"
done
echo "== 2. la regle est en base (donc add_rewrite_rule n'est PAS la cause) =="
wp --path=$W rewrite list --allow-root | grep -E "annonces/page" | sed 's/^/  /'
echo "== 3. desactiver les filtres de pagination ne change rien (donc posts_per_page=24 n'est PAS la cause) =="
echo "  (a faire par le dev : commenter optimize_property_queries + force_estatik_pagination, retester 1 et 2)"
echo "== 4. l'archive rend 17 cartes alors que le compteur JSON-LD voit 1 post =="
curl -s -H 'Cookie: pll_language=fr' "$B/fr/annonces/" | python3 -c "
import sys,re,json
h=sys.stdin.read()
print('  cartes dans le HTML :', h.count('pk-card'))
for m in re.finditer(r'application/ld\+json\">(.*?)</script>',h,re.S):
    try: d=json.loads(m.group(1))
    except Exception: continue
    for it in (d.get('@graph',[d]) if isinstance(d,dict) else d):
        if it.get('@type')=='ItemList': print('  ItemList: numberOfItems=',it.get('numberOfItems'),'elements=',len(it.get('itemListElement') or []))
"
echo "== 5. requete brute sur le meme CPT (prouve que les donnees sont la) =="
wp --path=$W eval '$q=new WP_Query(["post_type"=>"properties","post_status"=>"publish","posts_per_page"=>10,"paged"=>2]); echo "  found=".$q->found_posts." posts=".count($q->posts)." max=".$q->max_num_pages."\n";' --allow-root
echo "== 6. requete principale telle que WP la construit =="
cat > /tmp/diag-query.php <<'PHP'
<?php
$_SERVER['REQUEST_METHOD']='GET'; $_SERVER['HTTP_HOST']=wp_parse_url(get_option('home'),PHP_URL_HOST);
$_SERVER['REQUEST_URI']=$GLOBALS['argv'][1] ?? '/fr/annonces/page/2/';
unset($_GET); global $wp,$wp_query; $wp->parse_request(); $wp->query_posts();
echo "  vars=",json_encode(array_intersect_key($wp->query_vars,array_flip(['post_type','paged','page','lang','pagename','name']))),
   " found=",(int)$wp_query->found_posts," post_count=",(int)$wp_query->post_count,
   " is_404=",var_export($wp_query->is_404,true),"\n";
PHP
for u in /fr/annonces/ /fr/annonces/page/2/; do echo "  $u"; wp --path=$W eval-file /tmp/diag-query.php "$u" --allow-root 2>&1 | grep -E "^  vars=" | sed 's/^/    /'; done
echo
echo "CONCLUSION partielle (a confirmer par le dev) : la 404 ne vient ni des regles de"
echo "reecriture (elles sont en base), ni du posts_per_page force (desactive, meme 404)."
echo "L'indicateur decisif est l'etape 6 : la requete principale ne trouve rien sur une"
echo "page paginee alors que la requete equivalente en etape 5 trouve 10 posts -> ecarter"
echo "un filtre qui remplace la requete principale (query_vars filtres par un plugin) en"
echo "le neutralisant un par un, puis proposer le correctif AVEC cette sortie colle."
