# Rapport de qualification senior — Partikulier 6.15.0

## Objet

Ce rapport documente la correction de reproductibilité identifiée après la recette indépendante du dépôt `fcd1ac5`. La sandbox a été reconstruite depuis zéro avec WordPress et Polylang 3.8.7, puis la procédure du cahier des charges a été rejouée sans contourner les handlers métier.

## Correctifs appliqués

Le provisioning Polylang ne construit plus directement `PLL_Admin_Model` avec un tableau d’options. Il utilise le service de langues réellement initialisé par Polylang 3.8.7, avec vérification stricte des erreurs et vérification finale des langues FR/EN/AR.

Le test d’export CSV utilise maintenant un marqueur compatible avec la longueur SQL de `reference_code`, alimente `$_REQUEST` pour que `check_admin_referer()` soit réellement exercé en WP-CLI, et retourne un code non nul en cas d’échec. Le test de sécurité vérifie correctement le refus WordPress d’un auteur par redirection, au lieu d’exiger à tort un statut HTTP 403.

## Recette fraîche

| Contrôle | Résultat | Code retour |
|---|---:|---:|
| Provisioning Polylang 3.8.7 FR/EN/AR | PASS | 0 |
| Lot H dynamique | PASS | 0 |
| Lot K dynamique 1/20/100 lignes | PASS | 0 |
| Budget SQL K | 6 requêtes pour 1, 20 et 100 lignes | 0 |
| Export CSV par handler réel | PASS | 0 |
| BOM UTF-8 | `EF BB BF` | PASS |
| Neutralisation formule | `'=SUM(A1)` | PASS |
| Absence de secret dans CSV | PASS | 0 |
| E2E Polylang par `sync()` | PASS | 0 |
| Migration Polylang dry-run/apply | PASS | 0 |
| Contrôle qualité PHP/JS/version | PASS | 0 |
| Sécurité dynamique | 15/15 PASS | 0 |
| Audit UI public | PASS après redémarrage propre du serveur 6.15.0 | 0 |

Le parcours auteur vers `users.php` est refusé par redirection vers une page WordPress autorisée ; le test vérifie désormais l’absence d’accès effectif à la page protégée. Cette preuve est plus correcte que l’attente artificielle d’un 403.

## Résultats Polylang

Les langues FR, EN et AR sont créées avec Polylang 3.8.7. Le test E2E crée les traductions par le flux `sync()`, vérifie le cas auto EN seule, puis le remplacement par une traduction manuelle. L’auto-traduction remplacée passe en `draft`, la traduction manuelle reste `publish`. Le cas de méta legacy non numérique est journalisé comme `invalid_source_meta` et n’est pas casté silencieusement.

La migration répare les anciennes valeurs legacy en ID numérique, conserve `_pk_translation_source_legacy` et renseigne `_pk_source_lang`. Elle ne supprime aucun contenu.

## Verdict

La livraison est maintenant **reproductible sur une sandbox fraîche avec Polylang 3.8.7** sur le périmètre des contrôles ci-dessus. Les lots H et K sont validés par des exécutions dynamiques ; le provisioning Polylang n’est plus bloquant ; l’export CSV strict retourne bien un code d’échec en cas de nonce ou fixture invalide.

La qualification production reste conditionnée par l’exécution préalable de la migration Polylang en dry-run, une sauvegarde de base, puis `PK_APPLIQUER=1` selon la procédure du projet. Les preuves ont été produites sur sandbox et ne remplacent pas une recette sur une base de production anonymisée représentative.

## Preuves associées

Les sorties brutes sont archivées dans les rapports `rapport-*-fresh-6.15.0.*`, ainsi que dans les rapports H/K, Polylang, Estatik et sécurité déjà présents dans le dépôt.

**Commit correctif à publier :** après vérification de l’archive et du diff Git.
