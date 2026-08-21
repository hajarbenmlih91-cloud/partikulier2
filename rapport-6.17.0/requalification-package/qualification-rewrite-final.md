# Requalification finale — Partikulier 6.17.0

## Objet

Cette requalification traite la réserve portant sur les fiches EN/AR en 404 et sur les recettes dépendantes de fixtures historiques. Le provisioning et les tests ont été rejoués sur une sandbox fraîche alimentée par le bundle principal.

## Corrections appliquées

Les règles de réécriture du module `class-listing-urls.php` reconnaissent désormais les chemins Polylang préfixés : `/en/annonce/...` et `/ar/annonce/...`. Elles transmettent aussi la langue au query var WordPress. Le provisioning déplace le `flush_rewrite_rules(false)` après la configuration Estatik, Polylang, les traductions et les taxonomies, puis vérifie la persistance de `browser=1` avant le flush final.

La recette SEO découvre maintenant une famille `properties` publiée via Polylang, récupère ses IDs et permaliens réels, refuse les familles incomplètes et refuse toute réponse différente de 200. La recette Playwright découvre les pages Dépôt via leur template WordPress et préfère les fiches `/annonce` aux anciennes fiches `/property`, sans slug historique codé en dur.

## Preuves dynamiques

| Contrôle | Résultat |
|---|---|
| Famille Polylang découverte | FR `14`, EN `94`, AR `26` |
| Fiche FR géographique | `/annonce/casablanca/maarif/security-sofia-listing/` → HTTP 200 |
| Fiche EN géographique | `/en/annonce/casablanca/maarif/manual-test-en-14/` → HTTP 200 |
| Fiche AR géographique | `/ar/annonce/casablanca/maarif/security-sofia-listing/` → HTTP 200 |
| HTML AR | `lang="ar"`, `dir="rtl"` ; meta arabe localisée ; préfixe `إعلان-مترجم-` absent |
| hreflang | `fr`, `en`, `ar`, `x-default` sur les trois fiches |
| JSON-LD / Open Graph | cohérents avec `fr_FR`, `en_US` et `ar` ; meta EN/AR non françaises |
| Cache | MISS puis trois HIT publics par langue, tous HTTP 200 |
| Playwright AR | accueil, archive, fiche `/ar/annonce`, dépôt : PASS, `failures: []` |
| Playwright EN | accueil, archive, fiche `/en/annonce`, dépôt : PASS, `failures: []` |
| `browser=1` | persistant après provisioning et vérifié avant flush final |
| PHP | `class-listing-urls.php` et provisioning : aucune erreur de syntaxe |
| R6 | contrôle positif et test négatif PASS |

Les preuves détaillées sont archivées dans `seo-rewrite-annonce.json`, `journey-final.json` et le rapport JSON généré par `test-i18n-seo.sh`. Le résultat final est `passed: true` avec trois statuts 200, meta EN/AR localisées, slug arabe propre et MISS puis trois HIT publics par langue.

## Artefact

Le bundle contient le mu-plugin SEO, les fichiers `ar.mo` et `en_US.mo`, les deux WOFF2 Noto Sans Arabic et les scripts de recette. Le hash courant est :

```text
21f0c0b949c7796385022c08eda96d8a6ebaf999ebf0c446295c0af3cdfea19d  partikulier-6.17.0.zip
```

## Verdict

La réserve technique des fiches EN/AR est fermée sur le chemin `/annonce` et le bundle requalifié. La release est **techniquement requalifiée sur le périmètre code, packaging, réécriture, SEO, cache et navigateur**.

Le sign-off contractuel à 100 % reste conditionné aux attestations humaines de relecture native AR et EN si elles sont exigées par le CdC v1.5. Cette condition n’est pas remplacée par les tests automatisés.
