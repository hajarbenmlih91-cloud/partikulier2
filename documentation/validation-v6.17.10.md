# Rapport de validation — Partikulier 6.17.10

**Auteur : Manus AI**  
**Date : 23 août 2026**  
**Dépôt :** `hajarbenmlih91-cloud/partikulier2`  
**Branche candidate :** `correction-cdc-v6.17.10`  
**Base immuable :** `v6.17.7`, commit `6153debac8f84da46b1da95af1c810320dc7e5bf`

## Synthèse exécutive

Le cahier des charges a été appliqué sur une branche distincte, sans modifier `main` ni les tags historiques. L’environnement froid a été reconstruit avec WordPress 7.1, Estatik 4.3.4, Polylang 3.8.7 et Query Monitor 4.0.7. La recette finale contient **30 annonces `properties` publiées**, trois langues Polylang (`fr`, `en`, `ar`) et une famille de contenu traduite non nulle.

> **Résultat final : tous les contrôles d’acceptation exécutés sur l’instance froide sont passants.**

| Contrôle | Résultat final | Preuve versionnée |
|---|---:|---|
| Contrat HTTP direct | 16/16 | `documentation/routes-contract-v6.17.10.json` |
| E2E navigateur en contextes frais | 16/16 | `documentation/e2e-v6.17.10.json` |
| Régression visuelle | 30/30, dérive maximale ≤ 0,5 % | `documentation/visual-v6.17.10.json` et `tests/baselines-6.17.10/` |
| Négociation navigateur FR/EN/AR, cookie et robots | 10/10 | `documentation/browser-detection-v6.17.10.json` |
| Police arabe et RTL | 3/3 | `documentation/i18n-fonts-v6.17.10.json` |
| Famille de traductions | FR/EN/AR non nulle | `documentation/discover-i18n-family-v6.17.10.json` |
| HMAC HTTP réel | 5/5 rondes, faux/duplicate équilibrés | `documentation/hmac-http-v6.17.10.json` |
| Cas négatifs HMAC | 4/4 en HTTP 401 | `documentation/hmac-http-v6.17.10.json` |
| Mesure SQL archive | 47, 43, 43 requêtes | `documentation/sql-v6.17.10-summary.json` et traces complètes |
| Semgrep | 66 fichiers, 700 994 octets, 0 finding bloquant | `documentation/semgrep-v6.17.10.json` |
| Contrôle qualité local | Conforme | `documentation/check-v6.17.10.log` |
| Package | ZIP byte-identique sur deux builds | `documentation/partikulier-6.17.10.zip.sha256` |

## Environnement reproductible

Les versions sont fixées dans `scripts/install-tooling.sh`, `scripts/install.sh` et `documentation/environment-v6.17.10.json`. Le cold recipe installe PHP 8.3, MariaDB 10.11, WP-CLI 2.12.0, Node 22, Playwright et Semgrep 1.132.0, puis vérifie les versions des composants WordPress.

| Composant | Version validée |
|---|---:|
| Ubuntu | 24.04 amd64 |
| PHP | 8.3.6 |
| MariaDB | 10.11.14 |
| WordPress | 7.1 |
| Estatik | 4.3.4 |
| Polylang | 3.8.7 |
| Query Monitor | 4.0.7 |
| WP-CLI | 2.12.0 |
| Node.js | 22.13.0 |
| Playwright | 1.62.1 |
| Semgrep | 1.132.0 |

La source officielle Estatik est l’archive générique contrôlée par le header de version du plugin, car l’archive versionnée directe avec le nom attendu n’était pas disponible. Les archives Polylang et Query Monitor sont explicitement versionnées. Les sources officielles consultées sont indiquées dans la section Références.

## Corrections principales

Le contrat de routes a été durci afin de vérifier le statut HTTP, le nombre de redirections, l’URL finale, la langue HTML et la direction `ltr`/`rtl`. Le harnais navigateur utilise un contexte frais par scénario, et le harnais visuel génère ou vérifie 30 images baseline sur les combinaisons de langues, parcours et viewport desktop/mobile.

Le provisioning Polylang configure les langues FR/EN/AR, conserve le préfixe de la langue par défaut, active la négociation navigateur et réapplique les options après les liaisons de traduction afin de neutraliser l’état interne de première activation. Le slug français du parcours de dépôt est `deposer`, ce qui garantit directement `/fr/deposer/`. Le mu-plugin bloque les auto-redirections identiques du root et laisse la négociation humaine AR/EN ainsi que la priorité du cookie fonctionner.

Le cache de pages ne stocke plus les réponses HTML vides. Cette protection évite qu’un rendu transitoire vide soit transformé en réponse publique persistante et qu’il perturbe les contrôles de langue ou de direction. Le test de police prouve une requête Noto Sans Arabic effective, `document.fonts.check`, la famille calculée, `lang` et `dir`.

Le test HMAC utilise deux clients HTTP indépendants, signe le chemin REST canonique `/partikulier/v1/automation-event` avec le secret Base64 décodé, exécute cinq rondes concurrentes et vérifie les quatre rejets obligatoires : secret invalide, signature invalide, timestamp expiré et en-tête partagé absent. Aucun secret n’est enregistré dans les preuves.

## Reproduction

Depuis la racine du dépôt, la recette froide est :

```bash
PK_WP_DIR="$PWD/.runtime/wp-6.17.10" \
PK_DB_NAME=partikulier61710 \
PK_DB_USER=partikulier \
PK_DB_PASS='local-test-only' \
PK_PORT=8090 \
bash scripts/install.sh

PK_WP_DIR="$PWD/.runtime/wp-6.17.10" PK_PORT=8090 bash scripts/start.sh
```

Le package final est construit par `bash scripts/package.sh 6.17.10`. La vérification locale a produit deux archives byte-identiques. L’empreinte SHA-256 du bundle est générée après chaque build dans le fichier externe `documentation/partikulier-6.17.10.zip.sha256`. Elle ne doit pas être incluse dans l’archive elle-même, car cela créerait une dépendance circulaire.

## Limites et transparence

La validation HTTP a été exécutée sur le serveur PHP intégré utilisé par le cold recipe. Elle prouve le comportement WordPress/REST observable et les contrats de sécurité demandés, mais ne remplace pas une validation finale derrière le serveur web et l’infrastructure de production. Les performances SQL sont mesurées sous `SAVEQUERIES` sur l’archive de la fixture ; les valeurs sont donc des mesures de test instrumentées et non des benchmarks de production.

La configuration d’essai utilise les identifiants locaux indiqués par le script d’installation. Le secret HMAC employé pendant la recette a été éphémère et n’est pas présent dans le dépôt ni dans les rapports.

## Références

[1]: https://wordpress.org/download/ — WordPress.org, page officielle de téléchargement et prérequis.

[2]: https://wordpress.org/plugins/estatik/ — WordPress.org, fiche officielle Estatik.

[3]: https://downloads.wordpress.org/plugin/estatik.zip — Archive officielle Estatik contrôlée par sa version installée.

[4]: https://wordpress.org/plugins/polylang/ — WordPress.org, fiche officielle Polylang.

[5]: https://downloads.wordpress.org/plugin/polylang.3.8.7.zip — Archive officielle Polylang 3.8.7.

[6]: https://wordpress.org/plugins/query-monitor/ — WordPress.org, fiche officielle Query Monitor.

[7]: https://downloads.wordpress.org/plugin/query-monitor.4.0.7.zip — Archive officielle Query Monitor 4.0.7.
