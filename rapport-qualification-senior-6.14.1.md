# Rapport de qualification senior — Partikulier 6.14.1

## Verdict

La livraison 6.14.1 est qualifiée **conforme aux critères fonctionnels corrigés du CDC sur la sandbox de recette**, avec une réserve documentée sur la comparaison pixel historique lorsque le snapshot de données diffère. Les preuves ont été rejouées après synchronisation du thème dans WordPress et activation réelle de Polylang 3.8.7 et Estatik. Le verdict est fondé sur les rapports bruts ci-dessous, et non sur une baseline régénérée seule.

## Correctifs appliqués

| Sujet | Correction | Fichier |
|---|---|---|
| JSON-LD | Remplacement de la seconde valeur `EUR` par `MAD` dans `priceSpecification`. Le pays reste `MA`. | `theme/inc/class-jsonld.php` |
| Archive legacy | Interception `parse_request` prioritaire, en plus de `template_redirect`, pour garantir les 301 `/property/` et `/property/page/N/` avant un éventuel 404. | `theme/inc/class-listing-urls.php` |
| Polylang | Normalisation préalable de toutes les annonces publiées sans langue vers `fr`, puis création idempotente des familles EN/AR. | `scripts/provision-polylang.php` |
| Estatik E4 | Ajout des vrais handles Estatik 4.3.4 (`es-select2`, `es-slick`, `es-magnific`, `es-datetime-picker`) au nettoyage conditionnel hors parcours immobilier. | `theme/inc/class-estatik.php` |

## Preuves finales

### Contrôles statiques

- `scripts/check.sh` : PASS.
- 65 fichiers PHP : aucune erreur de syntaxe.
- 2 fichiers JavaScript : aucune erreur de syntaxe.
- Versions cohérentes : `6.14.1` dans les quatre fichiers contrôlés.
- Favoris : un seul gestionnaire `addEventListener` détecté.
- Recherche source `EUR` dans `theme/` et `scripts/` : aucune occurrence de devise résiduelle.

### Routes et SEO

| URL | Résultat observé |
|---|---|
| `/annonces/` | HTTP 200 |
| `/en/annonces/` | HTTP 200 |
| `/ar/annonces/` | HTTP 200 |
| `/property/` | HTTP 301 vers `/annonces/` |
| `/property/page/2/` | HTTP 301 vers `/annonces/page/2/` |

Sur une fiche réelle, le JSON-LD retourné contient deux occurrences `priceCurrency`, toutes deux à `MAD`. L’ancienne incohérence `MAD`/`EUR` n’est plus présente après synchronisation du thème.

### Polylang et taxonomies

Le provisioning a été rejoué avec Polylang 3.8.7 actif. Le rapport final confirme les slugs `fr`, `en`, `ar`, avec 19 sources FR, 20 contenus EN et 19 contenus AR. Les routes linguistiques FR/EN/AR répondent en 200. Après nettoyage des fixtures historiques de recette, `rapport-taxonomy-final-6.14.1.json` est en statut `PASS` avec zéro annonce publiée sans relation d’action ou de localisation.

Toute migration de production doit néanmoins être exécutée en dry-run puis validée par une sauvegarde de base. Le nouveau script `migrate-polylang-source-meta.php` conserve les valeurs legacy dans `_pk_translation_source_legacy` et ne répare que les groupes dont la source est déterminable de manière unique.

### Estatik et interactions

Les quatre parcours réels ont été rejoués avec Estatik 4.3.4 : archive, fiche, dépôt et favoris. Résultats observés :

| Parcours | HTTP | Console | Éléments observés |
|---|---:|---:|---|
| Archive | 200 | 0 erreur | 18 favoris, 18 images, 17 filtres |
| Fiche | 200 | 0 erreur | galerie 4, images 5, 3 contrôles favoris |
| Dépôt | 200 | 0 erreur | 5 formulaires, 7 filtres |
| Favoris | 200 | 0 erreur | page chargée correctement |

Le test interactif a ajouté puis supprimé le favori `postId=50`, avec `aria-pressed` passant à `true` puis `false`, et `localStorage` revenant à une liste vide. Statut : PASS.

### Lot B — N+1

La qualification retenue est **non-régression SQL**, pas gain de performance global. Aucun motif N+1 nouveau attribuable au thème n’a été détecté dans les profils avant/après existants. Les répétitions résiduelles liées à Polylang doivent rester surveillées séparément ; elles ne sont pas présentées comme supprimées.

### Visuel

La comparaison contre `tests/__baseline-6.13.1__` a été exécutée explicitement. Les pages stables `deposer`, `mes-annonces` et `404` restent sous le seuil (0,00–0,11 %). Les pages accueil/archive ont des hauteurs différentes et sont donc signalées à 100 % par le comparateur : le snapshot 6.13.1 contient moins de contenu que la sandbox actuelle. Ce résultat est une réserve de comparabilité des données, pas une preuve de zéro régression pixel historique. Les captures `.actuel.png` et `rapport-visual-baseline-6.13.1-final.txt` sont conservés pour revue.

## Procédure de reprise

Depuis la racine du dépôt :

```bash
bash scripts/install.sh
bash scripts/start.sh
wp --path=wp plugin install polylang --activate
wp --path=wp plugin install query-monitor --activate
wp --path=wp eval-file scripts/provision-polylang.php
wp --path=wp rewrite flush
bash scripts/check.sh
npm install --no-audit --no-fund
npx playwright install chromium
node scripts/real-ui-evidence.mjs
node scripts/test-estatik-interactions.mjs
bash scripts/package.sh 6.14.1
```

Les sorties doivent être conservées dans `rapport-*`, puis comparées aux rapports présents dans `preuves/6.14.1/INDEX.md`. Une installation de production doit être précédée d’une sauvegarde et d’un dry-run des taxonomies.

## Limites assumées

Cette qualification ne prétend pas démontrer un gain absolu de performance contre toutes les configurations d’hébergement. Elle démontre l’absence de régression fonctionnelle et de nouveau motif N+1 dans le protocole de recette disponible. Les tests visuels historiques doivent utiliser exactement le même snapshot de données et les mêmes assets si un zéro pixel diff historique est exigé.

**Conclusion :** les défauts bloquants signalés par les audits — devise EUR résiduelle, pagination legacy instable, provisioning Polylang non idempotent et handles Estatik incomplets — ont été corrigés et vérifiés sur la sandbox active. Le dépôt doit être livré avec ce rapport, les scripts et les rapports bruts associés.


## Addendum D-bis — cycle de vie des auto-traductions orphelines

### Correctif writer/reader et migration

Le contrat est désormais strict : `_pk_translation_source` contient exclusivement l’ID numérique du post source ; `_pk_source_lang` contient la langue de dépôt. Le reader refuse les anciennes valeurs non numériques et les retourne avec l’action `invalid_source_meta`, au lieu de les convertir silencieusement en zéro.

Le script `scripts/migrate-polylang-source-meta.php` est idempotent et possède deux modes explicites : dry-run par défaut et application via `PK_APPLIQUER=1`. Le test réel `scripts/test-polylang-migration.php` a démontré qu’une valeur legacy `fr` est planifiée sans mutation en dry-run, puis convertie en ID source avec archivage de la valeur historique en mode apply.

Les audits ont identifié un cas que la simple présence des langues FR/EN/AR ne couvre pas : Polylang éjecte une ancienne auto-traduction lorsqu’un administrateur lie une traduction manuelle dans la même langue. L’ancienne auto peut alors rester publiée hors du groupe source.

Le module `theme/inc/class-listing-translations.php` contient désormais `reconcile_orphans( $apply )`. Il retrouve la source via `_pk_translation_source`, lit le groupe Polylang actuel de la source, compare la langue de l’auto à l’ID actuellement associé et passe uniquement l’auto remplacée en `draft`. Aucune suppression et aucun `NOT EXISTS` global ne sont utilisés.

Le test E2E décisif `scripts/test-polylang-sync-e2e.php` appelle le vrai `Partikulier_Listing_Translations::sync()` et a produit :

```text
sync() FR/EN/AR       : map complète
source_raw EN/AR     : ID numérique de la source FR
source_lang EN/AR    : fr
auto seule            : publish
invalid legacy meta  : invalid_source_meta
remplacement manuel  : dry-run draft
apply                 : auto EN draft, manuel EN publish
résultat              : PASS
```

Le scénario `scripts/test-polylang-orphan-replacement.php` a produit :

```text
source FR       : 7
auto EN         : 87
manuel EN       : 88
groupe après auto   : {fr:7, en:87, ar:76}
groupe après manuel : {fr:7, en:88, ar:76}
auto après apply    : draft
manuel après apply  : publish
```

Le scénario `scripts/test-polylang-auto-only.php` a produit :

```text
source FR    : 90
auto EN      : 91
avant        : publish
après        : publish
résultat     : PASS
```

Le dry-run et l’application ont été vérifiés sur la sandbox. Le mode d’application est désormais `PK_APPLIQUER=1`, car `wp eval-file` ne doit pas dépendre d’un passage d’arguments ambigu. L’opération doit être précédée d’un backup en production.

Commandes D-bis :

```bash
wp --path=wp eval-file scripts/reconcile-polylang-orphans.php
wp --path=wp eval-file scripts/test-polylang-orphan-replacement.php
wp --path=wp eval-file scripts/test-polylang-auto-only.php
PK_APPLIQUER=1 wp --path=wp eval-file scripts/reconcile-polylang-orphans.php
```

Le lot D est validé sur la sandbox pour le flux réel `sync()`, le scénario auto seule, le remplacement manuel, le rejet des métadonnées invalides et la migration dry-run/apply. En production, la migration reste une opération à exécuter en priorité, en dry-run puis après sauvegarde.
