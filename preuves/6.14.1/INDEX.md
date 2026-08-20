# Index des preuves — Partikulier 6.14.1

Ce répertoire constitue le point d’entrée des preuves de recette. Les fichiers volumineux restent à la racine du dépôt afin de préserver la compatibilité avec les scripts historiques ; chaque entrée ci-dessous indique le fichier à ouvrir.

| Preuve | Fichier | Utilité |
|---|---|---|
| Contrôle statique | [`../../rapport-check-6.14.1.txt`](../../rapport-check-6.14.1.txt) | Syntaxe PHP/JS, versions et garde-fous |
| HTTP/SEO | [`../../rapport-curl-6.14.1.txt`](../../rapport-curl-6.14.1.txt) | Codes HTTP, redirections et contrôles publics |
| Diagnostic | [`../../rapport-diagnostic-6.14.1.txt`](../../rapport-diagnostic-6.14.1.txt) | Diagnostic WordPress, SEO et données structurées |
| Estatik UI | [`../../rapport-estatik-real-ui-6.14.1.json`](../../rapport-estatik-real-ui-6.14.1.json) | Archive, fiche, dépôt, favoris, assets et console |
| Estatik interactions | [`../../rapport-estatik-interactions-6.14.1.json`](../../rapport-estatik-interactions-6.14.1.json) | Clic favori, localStorage, galerie et erreurs JS |
| Polylang historique | [`../../rapport-polylang-6.14.1.json`](../../rapport-polylang-6.14.1.json) | Première preuve des langues et traductions |
| Polylang propre | [`../../rapport-polylang-clean-senior-6.14.1.json`](../../rapport-polylang-clean-senior-6.14.1.json) | Preuve sur sandbox fraîche |
| Taxonomies | [`../../rapport-taxonomy-clean-senior-6.14.1.json`](../../rapport-taxonomy-clean-senior-6.14.1.json) | Invariants action/localisation |
| N+1 avant | [`../../rapport-query-monitor-6.13.1.jsonl`](../../rapport-query-monitor-6.13.1.jsonl) | Profil de référence 6.13.1 |
| N+1 après | [`../../rapport-query-monitor-6.14.1.jsonl`](../../rapport-query-monitor-6.14.1.jsonl) | Profil de référence 6.14.1 |
| N+1 avant/après strict | [`../../rapport-query-monitor-before-6.13.1.jsonl`](../../rapport-query-monitor-before-6.13.1.jsonl) et [`../../rapport-query-monitor-after-6.14.1.jsonl`](../../rapport-query-monitor-after-6.14.1.jsonl) | Même dataset et mêmes parcours |
| Baseline historique | [`../../rapport-baseline-senior-6.14.1.json`](../../rapport-baseline-senior-6.14.1.json) | Comparaison 6.13.1/6.14.1 avec réserve filtre |
| Baseline courante | [`../../rapport-visual-final-senior-6.14.1.txt`](../../rapport-visual-final-senior-6.14.1.txt) | 12 vues, 0,00 % contre baseline 6.14.1 |
| Qualification | [`../../rapport-qualification-senior-6.14.1.md`](../../rapport-qualification-senior-6.14.1.md) | Verdict et réserves expliqués |
| Package | [`../../rapport-package-6.14.1.txt`](../../rapport-package-6.14.1.txt) | Contenu et contrôle de l’archive |

## Interprétation

Une preuve JSON/JSONL est la sortie brute d’un outil ; elle ne doit pas être remplacée par une reformulation marketing. Le rapport de qualification explique les liens entre les sorties. En cas de divergence, le développeur doit refaire la recette dans une sandbox fraîche et conserver le nouveau rapport avec une date, un commit et les versions des plugins.
