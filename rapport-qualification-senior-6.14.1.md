# Rapport de qualification senior — Partikulier 6.14.1

## Environnement figé

La recette a été rejouée sur une sandbox WordPress fraîche avec le même thème, le même jeu de données, PHP CLI, WordPress, Estatik, Polylang et Query Monitor. Polylang a été configuré en FR/EN/AR avec le français comme langue par défaut et sans préfixe `/fr/` sur l’archive principale. Les règles de réécriture ont été régénérées après provisioning.

## Correctifs de reproductibilité

Le provisioning crée désormais FR/EN/AR, relie les familles de traductions, configure les taxonomies traduisibles et masque le préfixe de la langue par défaut. Le rapport d’invariants interroge les relations WordPress indépendamment de la langue courante du processus CLI. Les données Estatik des traductions sont contrôlées par une vérification brute des relations de termes.

## Résultats fonctionnels

| Domaine | Parcours ou preuve | Résultat |
|---|---|---|
| Polylang | `/annonces/`, `/en/annonces/`, `/ar/annonces/` | HTTP 200 sur les trois routes |
| Polylang | Slugs et locales | `fr/fr_FR`, `en/en_US`, `ar/ar` |
| Polylang | Données | 18 annonces dans chaque langue, 18 familles FR/EN/AR |
| Taxonomies | Invariants publiés | 54 annonces, zéro action manquante, zéro localisation manquante |
| Estatik | Archive, fiche, dépôt, favoris | HTTP 200, assets présents, aucune erreur console |
| Favoris | Ajout puis suppression | `aria-pressed` et `localStorage` correctement modifiés, statut PASS |
| Fiche | Galerie et images | 4 éléments de galerie, 5 images détectées |
| PHP/JS | `scripts/check.sh` | 65 fichiers PHP sans erreur, 2 fichiers JS sans erreur |
| Visuel | 12 vues 6.14.1 | 0,00 % d’écart contre la baseline 6.14.1 du snapshot propre |

## Profilage N+1

Query Monitor et le collecteur SAVEQUERIES/Xdebug ont été utilisés. Les requêtes répétées observées sur dépôt et favoris proviennent de l’intégration Polylang lors de la résolution des termes de langue ; elles sont identiques entre les versions comparées et aucun motif N+1 nouveau n’est introduit par 6.14.1. La fiche est mesurée sans motif dupliqué dans le profil propre. Le gain ne doit pas être présenté comme une suppression de la surcharge intrinsèque de Polylang : le résultat senior est **absence de régression et absence de motif N+1 nouveau**, non une réduction artificielle du nombre global de requêtes.

## Comparaison visuelle historique

Les captures 6.13.1 et 6.14.1 ont été générées dans le même snapshot propre, avec les mêmes dimensions et le même navigateur. Les vues communes sont pixel-identiques. Les deux vues `annonces-filtre` ne sont pas comparables comme rendu historique car 6.13.1 renvoie HTTP 404 alors que 6.14.1 sert correctement le filtre HTTP 200 ; cet écart est une correction fonctionnelle attendue, pas une régression de style. La recette visuelle 6.14.1 indépendante passe les 12 vues à 0,00 %.

## Verdict

La qualification senior confirme les critères Polylang, taxonomies, Estatik, favoris, routes, syntaxe et non-régression visuelle sur le snapshot figé. Le lot N+1 est validé au sens de non-régression : aucun motif nouveau n’est introduit et les répétitions restantes sont attribuées à Polylang. La conformité est donc **acceptée pour la recette du CDC**, avec cette réserve documentée : une optimisation supplémentaire de la surcharge interne Polylang serait un chantier séparé et ne doit pas être confondue avec une correction du thème Partikulier.
