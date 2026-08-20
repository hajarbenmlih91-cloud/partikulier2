# Passation développeur — Partikulier 6.14.1

**Projet :** Partikulier, thème WordPress immobilier basé sur Estatik  
**Version livrée :** 6.14.1  
**Branche :** `main`  
**Dépôt :** [hajarbenmlih91-cloud/partikulier2](https://github.com/hajarbenmlih91-cloud/partikulier2)  
**Derniers commits de livraison :** `eca8c5c` et `8490fae`  
**Auteur du dossier :** Manus AI

## Objet du dossier

Ce dossier est une passation technique complète. Il décrit le cahier des charges appliqué, les fichiers modifiés, les décisions prises, les tests réellement exécutés, les preuves conservées, les limites qui ne doivent pas être masquées et la procédure à suivre pour refaire la recette dans un environnement WordPress propre. Le développeur repreneur ne doit pas avoir besoin d’une explication orale pour comprendre le travail.

> **Règle de lecture :** le ZIP `partikulier-6.14.1.zip` est le thème installable. Le dépôt complet contient en plus le code source, les scripts d’installation et de recette, les rapports JSON/JSONL, les baselines visuelles et la documentation de maintenance.

## Verdict de livraison

La livraison est acceptée pour le périmètre fonctionnel du CDC sur le snapshot de recette. Les routes publiques, Polylang FR/EN/AR, les taxonomies Estatik, les favoris, la galerie, le dépôt d’annonce, les contrôles PHP/JavaScript et la non-régression visuelle 6.14.1 ont été testés. Le lot B est validé comme **absence de régression N+1 introduite par le thème** : les motifs répétitifs restants sont localisés dans l’intégration Polylang et ne sont pas nouveaux dans 6.14.1.

La comparaison historique contient une différence fonctionnelle volontaire : le filtre `/annonces/?type=appartement` était en 404 avec 6.13.1 et répond en 200 avec 6.14.1. Cette différence est la correction attendue du CDC et ne doit pas être interprétée comme une régression visuelle.

## Navigation rapide

| Besoin | Document ou répertoire |
|---|---|
| Comprendre l’architecture | [`01-ARCHITECTURE-LIVREE.md`](./01-ARCHITECTURE-LIVREE.md) |
| Voir chaque changement | [`02-MODIFICATIONS-ET-DECISIONS.md`](./02-MODIFICATIONS-ET-DECISIONS.md) |
| Rejouer l’installation et la recette | [`03-REPRODUIRE-LA-RECETTE.md`](./03-REPRODUIRE-LA-RECETTE.md) |
| Lire la matrice de tests | [`04-MATRICE-ET-RESULTATS.md`](./04-MATRICE-ET-RESULTATS.md) |
| Connaître les réserves | [`05-LIMITES-ET-REPRISE.md`](./05-LIMITES-ET-REPRISE.md) |
| Rapport de qualification complet | [`../rapport-qualification-senior-6.14.1.md`](../rapport-qualification-senior-6.14.1.md) |
| Scripts reproductibles | [`../../scripts/LISEZ-MOI-SCRIPTS.md`](../../scripts/LISEZ-MOI-SCRIPTS.md) |
| Sources du thème | [`../../theme/`](../../theme/) |
| Package installable | [`../../partikulier-6.14.1.zip`](../../partikulier-6.14.1.zip) |
| Preuves brutes | [`../../preuves/6.14.1/`](../../preuves/6.14.1/) |

## Structure utile du dépôt

Le répertoire `theme/` est la source du thème. Le répertoire `scripts/` contient l’installation WordPress locale, la synchronisation, les contrôles statiques, le provisioning Polylang, les contrôles de taxonomies, les tests UI Estatik et les tests visuels. Le répertoire `documentation/` contient l’architecture, les décisions, le changelog et cette passation. Les fichiers `rapport-*` à la racine sont les sorties de recette conservées pour audit.

## Première prise en main

Le développeur doit commencer par lire ce document, puis `03-REPRODUIRE-LA-RECETTE.md`. Il doit ensuite exécuter `bash scripts/check.sh` avant toute modification. Pour une recette WordPress complète, il doit utiliser `bash scripts/install.sh`, installer Estatik, Polylang et Query Monitor dans la sandbox, lancer `bash scripts/start.sh`, puis exécuter les scripts de provisioning et de contrôle indiqués dans la procédure.

## Règle de modification

Ne pas modifier le design public, les classes CSS ou le markup visuel sans demande explicite. Les correctifs 6.14.1 ont été conçus pour rester localisés dans les modules PHP, les scripts de recette et les helpers d’URL/SEO. Toute modification ultérieure doit être accompagnée d’un nouveau rapport de tests, d’un changement de version ou d’une justification de non-changement selon le type de correction.
