#!/usr/bin/env bash
set -euo pipefail
BASE="${PK_URL:-http://localhost:8090}"
REPORT="${PK_REPORT:-/tmp/partikulier-6.17-seo.json}"
FR='property/security-sofia-listing/'
EN='en/property/manual-test-en-14/'
AR='ar/property/%d8%a5%d8%b9%d9%84%d8%a7%d9%86-%d8%a7%d9%84%d9%85%d8%aa%D8%b1%d8%ac%d9%85-security-sofia-listing/'
# Resolve the real Arabic URL from the current Polylang family when the encoded slug differs.
AR="$(wp --path=wp eval 'echo get_permalink((int) pll_get_post_translations(14)["ar"]);' 2>/dev/null | sed 's#http://localhost:8090/##' | tail -1)"
python3 - "$BASE" "$REPORT" "$FR" "$EN" "$AR" <<'PY'
import json, re, subprocess, sys
base, report, fr, en, ar = sys.argv[1:]
cases=[('fr',fr,'fr-FR','', 'fr_FR','fr_FR'),('en',en,'en-US','','en_US','en_US'),('ar',ar,'ar','rtl','ar','ar')]
rows=[]
for lang,path,html_lang,dirv,inlang,og in cases:
    body=subprocess.check_output(['curl','-sS',base+'/'+path],text=True)
    html=re.search(r'<html[^>]*',body)
    lang_ok=bool(html and re.search(r'lang="'+re.escape(html_lang)+r'"',html.group(0)))
    dir_ok=(dirv=='' and not (html and ' dir="rtl"' in html.group(0))) or (dirv=='rtl' and html and ' dir="rtl"' in html.group(0))
    ld_ok=bool(re.search(r'"inLanguage"\s*:\s*"'+re.escape(inlang)+r'"',body))
    og_ok=bool(re.search(r'property="og:locale" content="'+re.escape(og)+r'"',body))
    hreflangs=re.findall(r'<link[^>]+hreflang="([^"]+)"[^>]*>',body)
    h_ok=len(set(hreflangs))>=4 and 'x-default' in hreflangs and any(x in hreflangs for x in ['fr','fr-FR']) and 'en' in hreflangs and 'ar' in hreflangs
    desc=re.search(r'<meta name="description" content="([^"]*)"',body)
    rows.append({'lang':lang,'path':path,'html_lang':html.group(0) if html else '', 'lang_ok':lang_ok,'dir_ok':bool(dir_ok),'jsonld_inLanguage':inlang if ld_ok else None,'og_locale':og if og_ok else None,'hreflang':hreflangs,'meta_description':desc.group(1) if desc else '', 'passed':all([lang_ok,dir_ok,ld_ok,og_ok,h_ok])})
# Cache matrix: two requests for each localized path and body-language markers.
cache=[]
for lang,path,_,_,_,_ in cases:
    hits=[]
    jar=f'/tmp/pk-seo-{lang}.cookie'
    try: open(jar,'w').close()
    except OSError: pass
    # Première réponse : peut poser pll_language et ne doit pas être stockée.
    for i in range(4):
        raw=subprocess.check_output(['curl','-sS','-c',jar,'-b',jar,'-D','-','-o','/tmp/pk-seo-body',base+'/'+path],text=True)
        head,_,_=raw.partition('\r\n\r\n')
        body=open('/tmp/pk-seo-body',encoding='utf8',errors='ignore').read()
        status=head.splitlines()[0] if head.splitlines() else ''
        hits.append({'status':status,'cache_hit':'X-Partikulier-Cache: HIT' in head,'public_cache':'Cache-Control: public, max-age=43200' in head,'has_ar': 'dir="rtl"' in body,'has_lang': lang in body})
    cache.append({'lang':lang,'path':path,'requests':hits,'passed':all(x['status'].startswith('HTTP/1.1 200') and x['has_lang'] and x['public_cache'] for x in hits[1:]) and sum(1 for x in hits if x['cache_hit']) >= 2})
out={'passed':all(x['passed'] for x in rows+cache),'pages':rows,'cache':cache}
open(report,'w').write(json.dumps(out,ensure_ascii=False,indent=2))
print(json.dumps(out,ensure_ascii=False,indent=2))
sys.exit(0 if out['passed'] else 1)
PY
