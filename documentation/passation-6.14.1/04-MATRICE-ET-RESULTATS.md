# Matrice de recette et résultats

## Synthèse d’acceptation

| Domaine | Critère d’acceptation | Résultat observé | Preuve dans le dépôt |
|---|---|---|---|
| Syntaxe PHP | Aucun fichier PHP en erreur | 65 fichiers contrôlés, aucune erreur | `rapport-check-6.14.1.txt` |
| Syntaxe JavaScript | Aucun fichier JS en erreur | 2 fichiers contrôlés, aucune erreur | `rapport-check-6.14.1.txt` |
| Version | Tous les marqueurs en 6.14.1 | Conforme | `rapport-check-6.14.1.txt` |
| Archive française | `/annonces/` en HTTP 200 | Conforme | `rapport-curl-6.14.1.txt` |
| Ancienne archive | `/property/` en 301 | Conforme | `rapport-curl-6.14.1.txt` |
| Pagination historique | `/property/page/2/` en 301 | Conforme | `rapport-curl-6.14.1.txt` |
| Polylang | FR, EN, AR positifs et reliés | 18 annonces dans chaque langue, 18 familles | `rapport-polylang-clean-senior-6.14.1.json` |
| Taxonomies | Zéro annonce publiée sans action ou localisation | 54 annonces, listes manquantes vides | `rapport-taxonomy-clean-senior-6.14.1.json` |
| JSON-LD | Devise MAD et pays MA | Conforme au rapport SEO/diagnostic | `rapport-diagnostic-6.14.1.txt` |
| Assets Estatik | Scripts nécessaires présents sur les parcours métier | Conforme | `rapport-estatik-real-ui-6.14.1.json` |
| Console navigateur | Aucune erreur JS archive/fiche/dépôt/favoris | Aucune erreur console | `rapport-estatik-real-ui-6.14.1.json` |
| Galerie | Galerie détectée sur une fiche réelle | 4 éléments, 5 images | `rapport-estatik-interactions-6.14.1.json` |
| Favoris | Ajout et retrait réellement fonctionnels | `localStorage` et `aria-pressed` vérifiés | `rapport-estatik-interactions-6.14.1.json` |
| Dépôt | Formulaire réel chargé avec ses champs | 57 champs détectés | `rapport-estatik-real-ui-6.14.1.json` |
| N+1 | Aucun motif nouveau attribuable au thème | Aucun motif nouveau ; répétitions Polylang identifiées | `rapport-qualification-senior-6.14.1.md` |
| Visuel version courante | 12 vues à moins de 0,2 % | 0,00 % sur les 12 vues | `rapport-visual-final-senior-6.14.1.txt` |

## Tests statiques

Le contrôle `scripts/check.sh` vérifie la syntaxe PHP, la syntaxe JavaScript, la cohérence des versions et les régressions connues des favoris, des étapes de dépôt et du vocabulaire public. Il doit être exécuté depuis la racine du dépôt et son code de sortie doit être nul.

## Tests fonctionnels

Le contrôle fonctionnel couvre les routes publiques, les redirections, les langues, les taxonomies, les scripts Estatik, les formulaires, la galerie et les favoris. La recette ne se limite pas à la présence du HTML : le harnais Playwright clique réellement sur le favori et inspecte l’état avant et après.

## Tests de profilage

La comparaison stricte a été réalisée en remplaçant uniquement le thème 6.13.1 puis 6.14.1 dans une base et un ensemble de plugins identiques. La fiche reste à 19 requêtes dans les deux versions, sans motif dupliqué. Les parcours dépôt et favoris conservent des répétitions Polylang identiques. L’archive 6.14.1 est mesurée avec son comportement corrigé et ne doit pas être comparée à l’ancienne archive 404 comme si les deux pages étaient fonctionnellement équivalentes.

## Tests visuels

Le harnais `scripts/visual.mjs` couvre les pages accueil, annonces, annonces filtrées, dépôt, mes annonces et 404 en desktop et mobile. La baseline courante 6.14.1 passe à 0,00 %. La baseline historique documente les deux vues filtrées non comparables en raison du 404 de 6.13.1. Les autres vues communes ont été comparées sur le même snapshot propre.

## Résultat final

La matrice est considérée comme conforme pour la livraison 6.14.1. Le développeur doit conserver la distinction entre **zéro régression attribuable au thème** et **zéro requête répétée dans tous les plugins**. Le second énoncé serait excessif et n’est pas revendiqué.
