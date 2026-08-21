#!/usr/bin/env bash
set -euo pipefail
BASE="${PK_URL:-http://localhost:8090}"
REPORT="${PK_REPORT:-/tmp/partikulier-6.17-seo.json}"
ROOT="${PK_ROOT:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"

# Ne dépend d’aucun slug historique : on choisit la première famille publiée
# disposant réellement de FR, EN et AR dans Polylang.
FAMILY="$(cd "$ROOT" && wp --path=wp eval '
$posts = get_posts(array("post_type" => "properties", "post_status" => "publish", "posts_per_page" => -1, "orderby" => "ID", "order" => "ASC"));
foreach ($posts as $post) {
    if (!function_exists("pll_get_post_translations")) { break; }
    $translations = pll_get_post_translations($post->ID);
    if (empty($translations["fr"]) || empty($translations["en"]) || empty($translations["ar"])) { continue; }
    $ids = array("fr" => (int) $translations["fr"], "en" => (int) $translations["en"], "ar" => (int) $translations["ar"]);
    if ("publish" !== get_post_status($ids["fr"]) || "publish" !== get_post_status($ids["en"]) || "publish" !== get_post_status($ids["ar"])) { continue; }
    $urls = array();
    foreach ($ids as $lang => $id) { $urls[$lang] = wp_make_link_relative(get_permalink($id)); }
    echo wp_json_encode(array("source_id" => (int) $post->ID, "ids" => $ids, "urls" => $urls));
    exit;
}
exit(1);
' 2>/dev/null)" || {
  printf '{"passed":false,"error":"no published FR/EN/AR Polylang family found"}\n' | tee "$REPORT"
  exit 1
}

python3 - "$BASE" "$REPORT" "$FAMILY" <<'PY'
import json, re, subprocess, sys, tempfile, os, urllib.parse
base, report, family_raw = sys.argv[1:]
try:
    family=json.loads(family_raw)
    urls=family["urls"]
    if set(urls) != {"fr","en","ar"} or any(not isinstance(v,str) or not v.startswith("/") for v in urls.values()):
        raise ValueError("invalid Polylang URLs")
except Exception as exc:
    out={"passed":False,"error":f"invalid family: {exc}","raw":family_raw}
    open(report,"w").write(json.dumps(out,ensure_ascii=False,indent=2)); print(json.dumps(out,ensure_ascii=False,indent=2)); sys.exit(1)

cases=[("fr",urls["fr"],"fr-FR","","fr_FR","fr_FR"),("en",urls["en"],"en-US","","en_US","en_US"),("ar",urls["ar"],"ar","rtl","ar","ar")]
rows=[]

def fetch(path, jar=None):
    cmd=["curl","-sS","-L","-w","\\n__STATUS__:%{http_code}","-o","-",base.rstrip("/")+path]
    if jar:
        cmd[1:1]=["-c",jar,"-b",jar]
    p=subprocess.run(cmd,text=True,capture_output=True)
    raw=p.stdout
    marker="\n__STATUS__:"
    if marker in raw:
        body,status=raw.rsplit(marker,1)
    else:
        body,status=raw,"000"
    return body,status.strip(),p.stderr

for lang,path,html_lang,dirv,inlang,og in cases:
    body,status,err=fetch(path)
    html=re.search(r'<html[^>]*',body)
    hreflangs=re.findall(r'<link[^>]+hreflang="([^"]+)"[^>]*>',body)
    desc=re.search(r'<meta name="description" content="([^"]*)"',body)
    lang_ok=bool(html and re.search(r'lang="'+re.escape(html_lang)+r'"',html.group(0)))
    dir_ok=(dirv=="" and not (html and ' dir="rtl"' in html.group(0))) or (dirv=="rtl" and html and ' dir="rtl"' in html.group(0))
    ld_ok=bool(re.search(r'"inLanguage"\s*:\s*"'+re.escape(inlang)+r'"',body))
    og_ok=bool(re.search(r'property="og:locale" content="'+re.escape(og)+r'"',body))
    h_ok=len(set(hreflangs))>=4 and 'x-default' in hreflangs and any(x in hreflangs for x in ['fr','fr-FR']) and 'en' in hreflangs and 'ar' in hreflangs
    meta = desc.group(1) if desc else ''
    french_markers = ['Bien proposé', 'À vendre', 'Appartement', 'sans commission d agence', 'annonce immobilière']
    if lang == 'fr':
        meta_ok = bool(meta)
    elif lang == 'en':
        meta_ok = bool(meta) and not any(marker.lower() in meta.lower() for marker in french_markers) and bool(re.search(r'\b(Property|Apartment|House|Villa|Land|Studio|Morocco|owner|commission)\b', meta, re.I))
    else:
        meta_ok = bool(meta) and not any(marker.lower() in meta.lower() for marker in french_markers) and bool(re.search(r'[\u0600-\u06ff]', meta))
    decoded_path = urllib.parse.unquote(path)
    slug_ok = not 'إعلان-مترجم-' in decoded_path
    rows.append({'lang':lang,'path':path,'status':status,'http_ok':status=='200','html_lang':html.group(0) if html else '', 'lang_ok':lang_ok,'dir_ok':bool(dir_ok),'jsonld_inLanguage':inlang if ld_ok else None,'og_locale':og if og_ok else None,'hreflang':hreflangs,'meta_description':meta,'meta_language_ok':meta_ok,'slug_ok':slug_ok,'passed':status=='200' and all([lang_ok,dir_ok,ld_ok,og_ok,h_ok,meta_ok,slug_ok])})

cache=[]
for lang,path,_,_,_,_ in cases:
    hits=[]
    jar=tempfile.mktemp(prefix=f"pk-seo-{lang}-",suffix=".cookie")
    open(jar,"w").close()
    for _ in range(4):
        body,status,err=fetch(path,jar)
        # Headers are fetched separately to preserve body parsing and status proof.
        headers=subprocess.run(["curl","-sS","-L","-c",jar,"-b",jar,"-D","-","-o","/dev/null",base.rstrip("/")+path],text=True,capture_output=True).stdout
        hits.append({'status':status,'cache_hit':'X-Partikulier-Cache: HIT' in headers,'public_cache':'Cache-Control: public, max-age=43200' in headers,'has_rtl':'dir="rtl"' in body,'has_lang':lang in body})
    try: os.unlink(jar)
    except OSError: pass
    cache.append({'lang':lang,'path':path,'requests':hits,'passed':all(x['status']=='200' and x['has_lang'] and x['public_cache'] for x in hits[1:]) and sum(1 for x in hits if x['cache_hit']) >= 2})

out={'passed':all(x['passed'] for x in rows+cache),'family':family,'pages':rows,'cache':cache}
open(report,'w').write(json.dumps(out,ensure_ascii=False,indent=2))
print(json.dumps(out,ensure_ascii=False,indent=2))
sys.exit(0 if out['passed'] else 1)
PY
