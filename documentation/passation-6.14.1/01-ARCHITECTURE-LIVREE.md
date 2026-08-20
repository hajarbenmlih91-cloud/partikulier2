# Architecture livrée

## Vue d’ensemble

Partikulier 6.14.1 est un thème WordPress classique dont le rendu immobilier s’appuie sur le CPT `properties` et les taxonomies Estatik. Le thème ne remplace pas Estatik : il fournit l’intégration, les templates, les URLs publiques, les contrôles SEO, les assets front-end et les parcours métier autour du dépôt et des favoris.

| Couche | Emplacement | Responsabilité |
|---|---|---|
| Bootstrap du thème | `theme/functions.php` | Chargement des modules, version, hooks, archive `/annonces/`, helpers globaux |
| URL et réécriture | `theme/inc/class-listing-urls.php` | Archive francisée, redirections 301 depuis `/property/`, flush versionné |
| SEO | `theme/inc/class-seo.php` | Canonical, hreflang, meta descriptions, nettoyage Polylang |
| Données structurées | `theme/inc/class-jsonld.php` | JSON-LD immobilier, devise MAD et pays MA |
| Estatik | `theme/inc/class-estatik.php` | Overrides, contexte de chargement des assets, galerie et compatibilité plugin |
| Front-end | `theme/assets/js/main.js` | Favoris, interactions UI et comportements existants |
| Recette | `scripts/` | Installation, provisioning, contrôle statique, tests UI, profilage et rapports |
| Preuves | `rapport-*` et `preuves/6.14.1/` | Sorties brutes, rapports synthétiques et baselines |

## Flux de démarrage

Le fichier `theme/functions.php` charge les modules PHP du répertoire `theme/inc/`. `class-listing-urls.php` déclare l’archive et ses règles de compatibilité. `class-estatik.php` s’active uniquement si Estatik est présent et conserve les bibliothèques nécessaires sur archive, fiche, dépôt et favoris. Le nettoyage des assets ne retire pas jQuery global et ne désactive pas les dépendances nécessaires aux filtres, à la galerie ou aux favoris.

## URLs publiques

La route canonique française est `/annonces/`. Les langues secondaires utilisent `/en/annonces/` et `/ar/annonces/`. Les anciennes routes `/property/` et `/property/page/2/` sont redirigées en 301 vers les routes francisées correspondantes. Les liens de formulaire et de secours du template d’archive utilisent le helper canonique au lieu d’une URL codée en dur.

## Polylang

Le provisioning de recette configure trois langues : `fr` avec locale `fr_FR`, `en` avec locale `en_US` et `ar` avec locale `ar`. Le français est la langue par défaut sans préfixe, ce qui permet `/annonces/` au lieu de `/fr/annonces/`. Les types `properties` et `page`, ainsi que les taxonomies Estatik `es_type`, `es_category` et `es_location`, sont déclarés traduisibles.

Les scripts `provision-polylang.php` et `provision-polylang-taxonomies.php` créent ou relient les familles de traductions et les termes traduits. `repair-all-translation-terms.php` sert de réparation explicite lorsque des traductions de test existent mais ne possèdent pas encore de relations de termes. `report-polylang.php` vérifie les langues, locales, décomptes et familles. `report-taxonomy-invariants.php` interroge les relations de taxonomies indépendamment de la langue courante du processus CLI.

## Estatik et interactions

Les quatre parcours critiques sont l’archive, la fiche, le dépôt et les favoris. Le test `real-ui-evidence.mjs` vérifie HTTP, erreurs console, scripts Estatik, formulaires, filtres, galerie et images. Le test `test-estatik-interactions.mjs` effectue une vraie action de favori : clic d’ajout, vérification de `aria-pressed`, vérification de `localStorage`, clic de suppression et contrôle de la console.

## Profilage

`senior-http-profiler.php` est un mu-plugin temporaire de recette. Il enregistre à l’arrêt de chaque requête l’URL, la version du thème, le nombre de requêtes SQL, le temps SQL, la mémoire et les motifs SQL répétés avec leur appelant. Il doit rester un outil de qualification, pas une dépendance fonctionnelle du thème. Query Monitor fournit l’inspection applicative ; Xdebug fournit une trace PHP indépendante lorsque le serveur est lancé avec `xdebug.mode=profile`.
