# Validation de la candidate v6.17.11

Cette candidate est dérivée de `v6.17.10@85d59798b0ea0c9bf93f32ec7d02c883f3831493` et comparée à la base CDC `v6.17.7@6153debac8f84da46b1da95af1c810320dc7e5bf`. Le tag `v6.17.7` n’est pas modifié. La candidate contient les corrections de revue senior et les preuves versionnées correspondantes.

## Matrice de recette froide

| Contrôle | Résultat | Preuve |
|---|---:|---|
| WordPress / PHP / MariaDB / WP-CLI | 7.1 / 8.3.6 / 10.11.14 / 2.12.0 | `environment-v6.17.11.json` |
| Estatik / Polylang / Query Monitor | 4.3.4 / 3.8.7 / 4.0.7 | `environment-v6.17.11.json` |
| Fixture properties publiées | 30 | `environment-v6.17.11.json` |
| Routes contractuelles | 16/16 | `routes-contract-v6.17.11.json` |
| E2E navigateur | 16/16 | `e2e-v6.17.11.json` |
| Régression visuelle | 30/30, seuil 0,5 % | `visual-v6.17.11.json` et `tests/baselines-6.17.11/` |
| Browser/cookie/robots | 10/10 | `browser-detection-v6.17.11.json` |
| Font arabe/RTL | 3/3 | `i18n-fonts-v6.17.11.json` |
| Famille de traductions | FR/EN/AR, non vide | `discover-i18n-family-v6.17.11.json` |
| Tri prix/surface | 3 ordres, 24 lignes chacun | `search-sorting-v6.17.11.json` |
| HMAC HTTP | 5 rondes concurrentes, 4 rejets 401 | `hmac-http-v6.17.11.json` |
| SQL archive | 49 / 45 / 45, seuil 56 | `sql-v6.17.11-summary.json` et traces |
| Semgrep | 66 fichiers, 0 finding bloquant | `semgrep-v6.17.11.json` |
| PHP/JS/check | 66 PHP et 15 JS sans erreur | `check-v6.17.11.log` |

## Corrections de revue livrées

Le slug canonique de dépôt est maintenant `deposer` et l’ancien slug `deposer-une-annonce` est migré de façon idempotente lorsqu’il correspond au gabarit attendu. Les liens de dépôt, le SEO, le sitemap, les assets Estatik et le cache public utilisent les mêmes variantes localisées. Les pages de dépôt et d’espace personnel sont exclues du cache avant capture, y compris sur les chemins FR/EN/AR.

Le provisioning est strict : les erreurs Bash sont bloquantes, les noms de base et d’utilisateur sont validés, les apostrophes du mot de passe sont échappées, les identifiants administrateur et le secret HMAC sont configurables, et WP-CLI est installé depuis un artefact 2.12.0 vérifié par SHA-512. L’installation d’outils utilise Node 22 contrôlé, `npm ci` et le navigateur Playwright du lockfile.

Les harnais JSON écrivent désormais un JSON valide sur stdout et leurs résumés sur stderr. Le contrat HTTP suit les redirections jusqu’à la destination terminale et détecte les chaînes non terminales. Le test de tri exige une fixture significative ; le test visuel consigne l’URL finale des pages protégées. La CI contrôle aussi la validité structurelle des preuves et la présence des baselines.

## Réserves obligatoires

Cette validation ne constitue pas un sign-off CDC fermé inconditionnel. Le workflow GitHub v6.17.11 reste principalement statique/package : il ne provisionne pas WordPress/MariaDB et ne rejoue pas lui-même HTTP, navigateur, HMAC, SQL et cache. La source Estatik reste générique puis contrôlée par version, car l’URL versionnée testée renvoie 404 ; un miroir ou un checksum de l’archive exacte est encore nécessaire pour une reproductibilité externe forte.

Le rapport complet, ligne par ligne et priorisé, est disponible dans `senior-code-review-v6.17.11.md`.
