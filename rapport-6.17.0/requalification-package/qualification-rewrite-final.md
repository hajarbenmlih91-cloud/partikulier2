# Qualification finale de réécriture — Partikulier 6.17.0

## Objet

Cette requalification ferme la réserve observée sur les archives et fiches EN/AR après installation froide. Elle vérifie le chemin de reconstruction depuis le bundle, le provisioning Polylang 3.8.7, la persistance des règles de réécriture, les permaliens géographiques réels, le SEO multilingue, le cache et le parcours mobile.

## Corrections livrées

Le provisioning effectue le flush final après la configuration complète d’Estatik, Polylang, des traductions et des taxonomies. Les termes AR utilisent désormais un lexique explicite (`Appartement` → `شقة`, `Casablanca` → `الدار البيضاء`, etc.) et une migration idempotente corrige les anciens noms préfixés par `ترجمة`. La génération SEO applique en plus un nettoyage défensif des anciens termes afin qu’aucun marqueur artificiel ni fragment français ne puisse entrer dans la meta AR. Il rejoue également un flush WP-CLI `rewrite flush --hard` et vérifie la présence des règles linguistiques Polylang et de la query var `lang`. Le contrôle ne cherche plus les chaînes littérales `en/annonces` ou `ar/annonces`, qui ne sont pas la représentation interne des règles génériques Polylang `(en|ar)`.

La recette SEO découvre une famille `properties` publiée réellement dans Polylang et utilise ses IDs et permaliens courants. Le parcours navigateur utilise le même principe via `discover-i18n-family.php`; aucune ancienne fixture d’URL n’est imposée.

## Preuves dynamiques sur sandbox froide

| Contrôle | Résultat |
|---|---|
| Famille Polylang découverte | FR `14`, EN `94`, AR `26` |
| Archives | `/annonces/`, `/en/annonces/`, `/ar/annonces/` : HTTP `200` après alignement de l’URL locale de test |
| Fiche FR | `/annonce/casablanca/maarif/security-sofia-listing/` : HTTP `200` |
| Fiche EN | `/en/annonce/casablanca/maarif/manual-test-en-14/` : HTTP `200` |
| Fiche AR | `/ar/annonce/casablanca/maarif/security-sofia-listing/` : HTTP `200` |
| HTML AR | `lang="ar"` et `dir="rtl"` |
| hreflang | `fr`, `en`, `ar`, `x-default` sur les trois fiches |
| JSON-LD / Open Graph | `fr_FR`, `en_US` et `ar` cohérents avec les URLs |
| Meta EN | anglaise, sans fragments français détectés |
| Meta AR | `عقار مقترح مباشرة من مالكه، بدون عمولة وكالة. عقار في المغرب` ; sans fragments français ni `ترجمة` |
| Slug AR | préfixe parasite `إعلان-مترجم-` absent sur la fiche testée |
| Cache fiches | un MISS puis trois HIT publics par langue, HTTP `200` |
| `browser=1` | persistant après provisioning et contrôlé avant le flush final |
| Parcours navigateur AR | accueil, archive, fiche et dépôt : PASS, `failures: []` |
| Parcours navigateur EN | accueil, archive, fiche et dépôt : PASS, `failures: []` |
| Contrôle qualité | 66 fichiers PHP et 2 JavaScript : syntaxe valide; versions alignées en `6.17.0`; R6 positif et négatif PASS |

Les preuves brutes sont conservées dans `/tmp/pk617-seo-final.json` et `/tmp/pk617-journeys-final.json` pendant la recette. Le rapport SEO final retourne `passed: true`; le rapport navigateur final retourne `passed: true` avec deux listes `failures` vides.

## Artefact final

Le package installable régénéré est :

```text
partikulier-6.17.0.zip
SHA-256: 32315c29cd3373284b1e4b9d92aea679ddab01920d64de1a344bc2a18e547137
```

Le package thème seul est :

```text
partikulier-6.17.0-theme.zip
SHA-256: fab29588cdbd6dd51cc4c0542ccab72dccc806983b6de2c9755bbf02841db214
```

Le bundle installable contient notamment le thème, `theme/languages/partikulier.pot`, les catalogues `.mo`, le mu-plugin `mu-plugins/partikulier-early-seo.php` et les deux polices Noto Sans Arabic. Les scripts de recette et le présent rapport restent dans le dépôt de développement afin de séparer l’artefact WordPress installable des outils d’audit.

## Verdict

La réserve technique des routes EN/AR est fermée : le provisioning froid génère les règles nécessaires, les archives et fiches réelles répondent `200`, le SEO est cohérent, les métadonnées EN/AR sont localisées, le slug arabe testé est propre, le cache produit les MISS/HIT attendus et le parcours mobile est vert.

La release est donc **techniquement requalifiée sur le périmètre code, packaging, réécriture, SEO, cache et parcours navigateur**. La relecture humaine native de l’arabe et de l’anglais reste une condition éditoriale distincte si le CdC v1.5 l’exige; elle ne peut pas être certifiée par une recette automatisée.
