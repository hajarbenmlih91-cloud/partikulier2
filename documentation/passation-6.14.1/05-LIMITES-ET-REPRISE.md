# Limites, réserves et reprise

## Ce qui est garanti

La livraison garantit que le code source, les scripts de recette et les rapports présents dans le dépôt correspondent à la qualification 6.14.1 décrite dans `00-LISEZ-MOI.md`. Les routes, le multilingue de recette, les taxonomies, les interactions Estatik, les contrôles statiques et la baseline courante sont documentés par des sorties conservées.

## Ce qui n’est pas garanti par une sandbox locale

Une sandbox locale ne remplace pas une validation sur l’hébergement de production. Les versions PHP, MariaDB, Estatik, Polylang, les caches, le CDN, les règles Nginx/Apache, les permissions de fichiers et les données réelles peuvent changer le résultat. Avant mise en production, le développeur doit refaire au minimum les contrôles HTTP, la console navigateur, le dépôt d’annonce, les favoris, la galerie, le sitemap, le canonical et les trois langues sur une copie de staging.

## Réserve sur le lot B

La preuve senior établit l’absence de motif N+1 nouveau attribuable à 6.14.1 dans les parcours comparés. Les répétitions observées sur dépôt et favoris sont liées à Polylang et existent dans les deux versions. Pour une optimisation plus poussée, il faudrait profiler l’application réelle avec les données de production anonymisées, le cache de production et un outil de profilage PHP compatible avec l’hébergement. Il ne faut pas transformer le script de collecte en fonctionnalité permanente sans examiner l’impact de `SAVEQUERIES`.

## Réserve sur la comparaison visuelle historique

La baseline 6.13.1 est authentique pour le package historique disponible dans le dépôt. Le filtre de l’archive n’est pas comparable pixel à pixel puisque 6.13.1 renvoie 404 et 6.14.1 renvoie 200. L’écart est volontaire et correspond à la correction de route. Pour une homologation graphique stricte, il faut obtenir une baseline 6.13.1 générée avec le même comportement de filtre attendu, ou accepter formellement l’écart fonctionnel.

## Nettoyage après recette

Le mu-plugin `senior-http-profiler.php` ne doit pas rester actif sur production. Supprimer `wp-content/mu-plugins/senior-http-profiler.php`, retirer `SAVEQUERIES` de `wp-config.php` si cette constante a été ajoutée uniquement pour la recette, vider les caches et régénérer les permaliens. Les traces Xdebug doivent rester hors du répertoire public.

## Dépannage courant

| Symptôme | Cause probable | Action |
|---|---|---|
| `/annonces/` en 404 | Règles non flushées ou préfixe Polylang FR actif | Régénérer les permaliens et vérifier `hide_default` |
| Traductions positives mais taxonomies manquantes | Termes présents sans relations sur les posts traduits | Exécuter `repair-all-translation-terms.php`, puis les invariants |
| Galerie vide | Assets Estatik supprimés hors contexte | Vérifier `class-estatik.php` et le contexte de fiche |
| Favori sans changement | Script principal absent ou double listener | Vérifier `main.js`, `scripts/check.sh` et la console |
| Baseline à 100 % d’écart | Mauvais port, mauvaise route, données différentes ou dimensions différentes | Vérifier l’URL, le snapshot, le navigateur et les hauteurs d’image |
| Query Monitor non visible | Barre admin absente sur visite non connectée | Utiliser le profiler SAVEQUERIES/Xdebug en complément |
| Package incomplet | Archive générée sans argument de version | Utiliser `bash scripts/package.sh 6.14.1` |

## Reprise par un nouveau développeur

Le développeur repreneur doit créer une branche dédiée, lire le présent dossier, lancer le contrôle statique, puis refaire la recette sur staging. Toute divergence doit être consignée dans un nouveau rapport daté. Il doit comparer les rapports JSON/JSONL plutôt que se fier à une affirmation textuelle. Une modification de code n’est considérée comme terminée qu’après mise à jour du changelog, exécution de la matrice pertinente et mise à jour du package si le thème installable est concerné.


## D-bis — auto-traductions Polylang orphelines

Polylang peut éjecter une auto-traduction du groupe lorsqu’une traduction manuelle est ensuite liée dans la même langue. Le thème conserve `_pk_translation_source` et réconcilie maintenant ces cas dans `class-listing-translations.php`.

La règle est prudente : une auto seule reste publiée ; une auto dont la langue est désormais occupée par un autre post du groupe source passe en brouillon. Les contenus ne sont jamais supprimés automatiquement.

Avant production, exécuter :

```bash
wp --path=wp eval-file scripts/reconcile-polylang-orphans.php
wp --path=wp eval-file scripts/reconcile-polylang-orphans.php -- --apply
```

Le premier appel est non destructif. Le second exige une sauvegarde préalable de la base et doit être journalisé. Les tests d’acceptation sont `scripts/test-polylang-orphan-replacement.php` et `scripts/test-polylang-auto-only.php`.
