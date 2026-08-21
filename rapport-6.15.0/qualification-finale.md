# Qualification finale — Partikulier 6.15.0

**Date de recette :** 21 août 2026
**Environnement :** WordPress local, PHP 8.4, Polylang 3.8.7, MariaDB, WP-CLI
**Référence contractuelle :** CdC Partikulier 6.15 Backoffice v2.2

## Décision

La livraison **6.15.0 est qualifiée PASS sur le périmètre H/K et les réserves contractuelles précédemment ouvertes sont clôturées par code et par tests dynamiques**. Cette qualification repose sur une sandbox WordPress reconstruite, Polylang 3.8.7 provisionné en FR/EN/AR et les rapports JSON archivés dans ce dossier.

## Corrections finales appliquées

Le provisioning Polylang ne dépend plus de `new PLL_Admin_Model( $array )`. Il utilise le modèle déjà initialisé par Polylang 3.8.7 et rapporte les objets `PLL_Language` réels. Les rapports évitent également `pll_languages_list( [ 'fields' => ... ] )`, qui retournait des valeurs nulles dans cette installation, et mappent explicitement `slug` et `locale`.

Le tableau Leads utilise une sous-classe native `WP_List_Table`, avec colonnes admin, pagination native et classes `widefat fixed striped`. Le rendu métier est réutilisé, sans CSS public ni dépendance CDN. Le tri est maintenant déclaré par `get_sortable_columns()` et limité aux clés contractuelles `last_seen_at`, `consent` et `status`.

La requête de liste applique une whitelist interne d’expressions SQL (`first_seen_at`, `last_seen_at`, `COALESCE(f.status, 'new')`, `c.granted_at`) et accepte uniquement `ASC` ou `DESC`. Toute valeur `orderby` ou direction inconnue retombe sur le tri par défaut `last_seen_at DESC`, avec un tie-breaker `l.id DESC`. Aucune valeur fournie par l’utilisateur n’est interpolée comme expression SQL.

La méta n8n est désormais écrite sous le nom contractuel `_pk_credentials_last_resend_accepted_at`. Une migration one-shot copie les anciennes valeurs `_pk_credentials_last_resent_at` vers la nouvelle clé, conserve l’ancienne comme trace de compatibilité et utilise une option de migration versionnée. La lecture de l’accusé reste rétrocompatible et l’écriture de la nouvelle demande ne recrée jamais l’ancienne méta.

## Preuves dynamiques

| Contrôle | Résultat | Preuve |
|---|---:|---|
| Lot H — options, cache, fallback FR, routes | PASS | `rapport-lot-h-final-6.15.0.json` |
| Lot K — rendu `WP_List_Table` | PASS | `rapport-lot-k-final-6.15.0.json` |
| Lot K — budget SQL 1/20/100 lignes | PASS, 6 requêtes additionnelles dans chaque cas | `rapport-lot-k-final-6.15.0.json` |
| Lot K — warnings PHP 8.4 | PASS, `runtime_messages` vide | `rapport-lot-k-final-6.15.0.json` |
| Lot K — neutralisation CSV hostile | PASS | `rapport-lot-k-final-6.15.0.json` |
| Contrat méta n8n | PASS, migration, trace legacy, écriture nouvelle clé et idempotence | `rapport-k-contract-final-6.15.0.json` |
| Contrat tri | PASS, ASC, DESC et entrée hostile rejetée | `rapport-k-contract-final-6.15.0.json` |
| Polylang provisioning 3.8.7 | PASS, FR/EN/AR présents | `rapport-polylang-fresh-6.15.0.json` |
| Polylang sync E2E | PASS | `rapport-polylang-e2e-final-6.15.0.json` |
| Migration Polylang | PASS, dry-run non destructif puis apply | `rapport-migration-fresh-6.15.0.json` |
| Contrôle qualité global | PASS, 65 PHP et 2 JavaScript sans erreur | `rapport-check-final-6.15.0.log` |
| Archive ZIP | PASS, package reconstruit et `unzip -t` sans erreur | `rapport-package-final-6.15.0.sha256` |

## Mesures Lot K

Les scénarios 1, 20 et 100 lignes ont tous produit un rendu de tableau et un bouton d’export. Le nombre mesuré est de **6 requêtes additionnelles** par page, donc inférieur au budget contractuel de 15, indépendamment du nombre de lignes affichées. Aucun avertissement, notice ou dépréciation n’a été capturé par le handler strict.

Le test contractuel indépendant confirme également que le tri ascendant place la fixture ancienne en premier, que le tri descendant place la fixture récente en premier et qu’un `orderby` hostile tel que `(SELECT` avec une direction `DROP` retombe sur le tri par défaut sans erreur SQL.

Le contrôle CSV confirme que `=SUM(A1)` devient `'=SUM(A1)` et qu’une valeur ordinaire reste inchangée. Les colonnes exportées ne contiennent pas le téléphone en clair.

## Polylang

Le rapport fraîchement généré confirme les trois langues et leurs locales : `fr/fr_FR`, `en/en_US`, `ar/ar`. Les familles de traductions sont liées pour chaque source française. Le test E2E confirme le remplacement d’une auto-traduction anglaise par une traduction manuelle sans publication simultanée de l’ancienne auto-traduction.

## Package

```text
Fichier : partikulier-6.15.0.zip
Taille  : 539K
SHA-256 : fb7dd8dffe6e9281c9d24171fcc6dc7812f89884034e106b15f80d1fe530d819
```

Le hash a été recalculé indépendamment après `package.sh 6.15.0`, puis vérifié avec `unzip -t` sans erreur.

## Conclusion contractuelle

Les deux écarts identifiés par la contre-revue sont maintenant traités : **le nom de méta correspond au CDC v2.2 avec migration rétrocompatible**, et **le tri est livré avec une whitelist stricte et un test négatif**. Le sign-off final est enregistré après recalcul du package, mise à jour de son empreinte et vérification du commit poussé.
