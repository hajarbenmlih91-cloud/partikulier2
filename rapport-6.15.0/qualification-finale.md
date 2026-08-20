# Qualification finale — Partikulier 6.15.0

**Date de recette :** 21 août 2026
**Environnement :** WordPress local, PHP 8.4, Polylang 3.8.7, MariaDB, WP-CLI
**Référence contractuelle :** CdC Partikulier 6.15 Backoffice v2.2

## Décision

La livraison **6.15.0 est qualifiée PASS** pour les lots H et K testés sur la sandbox reconstruite avec les scripts versionnés. La qualification est fondée sur les rapports JSON archivés dans ce dossier et non sur une inspection manuelle seule.

## Corrections finales appliquées

Le provisioning Polylang ne dépend plus de `new PLL_Admin_Model( $array )`. Il utilise le modèle déjà initialisé par Polylang 3.8.7 et rapporte les objets `PLL_Language` réels. Les rapports évitent également `pll_languages_list( [ 'fields' => ... ] )`, qui retournait des valeurs nulles dans cette installation, et mappent explicitement `slug` et `locale`.

Le tableau Leads utilise désormais une sous-classe native `WP_List_Table`, avec colonnes admin, pagination native et classes `widefat fixed striped`. Le rendu métier est réutilisé, sans CSS public ni dépendance CDN. Le harness CLI renseigne les variables serveur minimales attendues par WordPress afin que les warnings PHP 8.4 ne soient pas masqués mais correctement attribués.

## Preuves dynamiques

| Contrôle | Résultat | Preuve |
|---|---:|---|
| Lot H — options, cache, fallback FR, routes | PASS | `rapport-lot-h-fresh-6.15.0.json` |
| Lot K — rendu `WP_List_Table` | PASS | `rapport-lot-k-fresh-6.15.0.json` |
| Lot K — budget SQL 1/20/100 lignes | PASS, 6 requêtes additionnelles dans chaque cas | `rapport-lot-k-fresh-6.15.0.json` |
| Lot K — warnings PHP 8.4 | PASS, tableau `runtime_messages` vide | `rapport-lot-k-fresh-6.15.0.json` |
| Lot K — neutralisation CSV hostile | PASS | `rapport-csv-fresh-6.15.0.json` et CSV associé |
| Polylang provisioning 3.8.7 | PASS, FR/EN/AR présents | `rapport-polylang-fresh-6.15.0.json` |
| Polylang sync E2E | PASS | `rapport-polylang-e2e-fresh-6.15.0.json` |
| Migration Polylang | PASS, dry-run non destructif puis apply | `rapport-migration-fresh-6.15.0.json` |
| Contrôle qualité global | PASS, 65 PHP et 2 JavaScript sans erreur | `rapport-check-fresh-6.15.0.log` |
| Archive ZIP | PASS, test `unzip -t` sans erreur | `rapport-package-6.15.0.log` |

## Mesures Lot K

Les scénarios 1, 20 et 100 lignes ont tous produit un rendu de tableau et un bouton d’export. Le nombre mesuré est de **6 requêtes additionnelles** par page, donc inférieur au budget contractuel de 15, indépendamment du nombre de lignes affichées. Aucun avertissement, notice ou dépréciation n’a été capturé par le handler strict.

Le contrôle CSV confirme que `=SUM(A1)` devient `'=SUM(A1)` et qu’une valeur ordinaire reste inchangée. Les colonnes exportées ne contiennent pas le téléphone en clair.

## Polylang

Le rapport fraîchement généré confirme les trois langues et leurs locales : `fr/fr_FR`, `en/en_US`, `ar/ar`. Les volumes publiés sont `3/3/3` et les familles de traductions sont liées pour chaque source française. Le test E2E confirme le remplacement d’une auto-traduction anglaise par une traduction manuelle sans publication simultanée de l’ancienne auto-traduction.

## Package

```text
Fichier : partikulier-6.15.0.zip
Taille  : 538K
SHA-256 : 45b219b1cb5ff9eb2aafcbd54db30145d1604afc372fa2e6dc52dfd6d04d905a
```

> Le hash ci-dessus est celui calculé après `package.sh 6.15.0`, puis recalculé indépendamment par `sha256sum`. L’archive passe `unzip -t`.

## Réserve explicitement clôturée

Le premier passage du test K avait révélé deux warnings `Undefined array key HTTP_HOST` dans le code natif de WordPress, uniquement parce que le contexte WP-CLI ne définissait pas les variables serveur utilisées pour construire les liens de pagination. Le test a été corrigé pour reproduire le contexte admin minimal (`HTTP_HOST`, `REQUEST_URI`, `REQUEST_METHOD`) puis rejoué. Le second passage est PASS avec zéro message runtime. Il ne s’agit donc pas d’une suppression de warning, mais d’une correction du harnais de preuve.
