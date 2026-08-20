# Modifications et décisions techniques

## Synthèse par lot

| Lot | Implémentation livrée | Fichiers principaux | Preuve |
|---|---|---|---|
| A — URLs | Archive `/annonces/`, redirections anciennes URLs, flush versionné | `theme/functions.php`, `theme/inc/class-listing-urls.php`, `theme/templates/archive.php` | `rapport-curl-6.14.1.txt` |
| B — N+1 | Contrôle des motifs SQL et comparaison avant/après ; aucun motif nouveau attribué au thème | `scripts/senior-http-profiler.php` | `rapport-qualification-senior-6.14.1.md` |
| D — Polylang | FR/EN/AR, archive sans préfixe FR, familles et taxonomies reliées | `scripts/provision-polylang.php`, `scripts/provision-polylang-taxonomies.php` | `rapport-polylang-clean-senior-6.14.1.json` |
| E1 — JSON-LD | Devise `MAD`, pays `MA` et données structurées cohérentes | `theme/inc/class-jsonld.php` | `rapport-diagnostic-6.14.1.txt` |
| E4 — Assets | Déchargement conditionnel hors parcours Estatik ; conservation galerie/filtres/dépôt/favoris | `theme/inc/class-estatik.php` | `rapport-estatik-real-ui-6.14.1.json` |
| E5 — Favoris | Un gestionnaire central, comportement localStorage et style conservés | `theme/assets/js/main.js`, `scripts/check.sh` | `rapport-estatik-interactions-6.14.1.json` |
| F — Taxonomies | Provisioning et réparation des termes traduits ; contrôle zéro annonce sans action/localisation | `scripts/repair-all-translation-terms.php`, `scripts/report-taxonomy-invariants.php` | `rapport-taxonomy-clean-senior-6.14.1.json` |
| G — Version | Alignement de tous les marqueurs sur `6.14.1` | `theme/style.css`, `theme/functions.php`, `theme/package.json`, `theme/readme.txt`, `package.json` | `rapport-check-6.14.1.txt` |

## Décisions importantes

### Ne pas refondre le design

Le CDC demandait de conserver le thème visuel. Les changements ont donc privilégié les hooks, helpers, règles de réécriture, données structurées et scripts de recette. Les captures de baseline 6.14.1 servent de contrôle de non-régression, et non de justification pour modifier le CSS.

### Garder les assets Estatik dans les parcours métier

Le nettoyage agressif des scripts aurait pu casser la galerie, les filtres, le dépôt ou les favoris. `dequeue_heavy()` ne retire les bibliothèques lourdes que hors contexte immobilier. Dans les contextes archive, fiche, dépôt, tableau de bord et favoris, les dépendances restent disponibles. Cette décision est vérifiée par le rapport UI réel.

### Mesurer le N+1 par motif et par appelant

Un nombre global de requêtes ne suffit pas. Le profiler conserve les motifs normalisés et leur appelant. Les répétitions Polylang sont identifiées séparément. Le verdict B porte sur l’absence de motif nouveau ou de régression attribuable au thème ; il ne prétend pas optimiser le code interne de Polylang.

### Interroger les taxonomies sans dépendre de la langue CLI

Le premier contrôle pouvait conclure à tort que des traductions n’avaient pas de termes parce que la langue active de WP-CLI filtrait la lecture. Le contrôle final utilise `wp_get_object_terms()` avec langue désactivée et filtre supprimé, puis WP-CLI a été utilisé pour confirmer les relations brutes. Cette correction rend le résultat métier exploitable.

### Ne pas maquiller l’écart historique du filtre

En 6.13.1, le parcours `/annonces/?type=appartement` répondait 404. En 6.14.1, il répond 200. Une comparaison pixel stricte entre ces deux états ne serait pas une preuve de non-régression : ce sont deux comportements fonctionnels différents. Le rapport conserve cet écart comme correction attendue et mesure séparément les 12 vues 6.14.1 contre leur baseline propre.

## Fichiers nouveaux de recette

`provision-polylang-taxonomies.php` crée les termes traduits et les groupes de langues. `repair-all-translation-terms.php` recopie les relations de taxonomies sur les traductions. `senior-http-profiler.php` collecte les mesures SQL et mémoire. Ces scripts sont destinés à la recette et à la reprise développeur ; ils ne sont pas des fonctionnalités exposées aux visiteurs.
