# Revue de code senior — v6.17.15

**Auteur :** Manus AI  
**Base CDC :** `v6.17.7@6153debac8f84da46b1da95af1c810320dc7e5bf`  
**Parent :** `v6.17.14@4f13bdb70bab5db8b1a4ddf10ac3d062daef3ef5`  
**Périmètre :** provisioning, artefact Estatik, CI, acceptance froide, package, sécurité, routes et preuves. Les tags v6.17.7, v6.17.11, v6.17.12 et v6.17.13 ne sont pas réécrits.

## Verdict

Les deux réserves majeures identifiées lors de la revue v6.17.11 ont été traitées dans la candidate v6.17.15. Estatik 4.3.4 est maintenant embarqué sous `vendor-artifacts/`, vérifié par SHA-256 avant installation et inclus dans le bundle. Un job GitHub Actions `cold-acceptance` reconstruit un WordPress/MariaDB neuf et exécute les suites HTTP, navigateur, visuelle, i18n, HMAC, SQL et Semgrep. Le job `package` dépend de `cold-acceptance`, et le job `release` dépend du package et n’est activé que pour le tag v6.17.15.

> **Verdict senior : les réserves bloquantes de v6.17.11 sont corrigées. La candidate v6.17.15 est techniquement prête pour une validation CI GitHub finale ; la release ne doit être créée qu’après le succès effectif du workflow sur le tag.**

Le fallback vers l’URL générique WordPress.org est conservé uniquement pour un besoin exceptionnel et doit être activé explicitement avec `PK_ALLOW_UNPINNED_ESTATIK=1`. Ce mode est signalé comme **non reproductible** et ne doit pas être utilisé par la CI ni par la release.

## Constats et corrections

| Priorité | Localisation | Constat | Correction v6.17.15 | Statut |
|---|---|---|---|---|
| **Bloquant corrigé** | `scripts/install.sh:36–39, 140–158` | Estatik était téléchargé depuis une URL générique sans identité binaire contrôlée. | L’installateur privilégie `vendor-artifacts/estatik-4.3.4.zip`, exige son `.sha256`, exécute `sha256sum --check --strict`, puis vérifie la version WordPress installée. | **PASS** sur installation froide neuve ; Estatik 4.3.4 installé. |
| **Bloquant corrigé** | `vendor-artifacts/estatik-4.3.4.zip` | Il manquait un artefact local stable permettant de reconstruire la recette sans dépendre d’un changement futur de WordPress.org. | Archive récupérée depuis la source officielle, inspectée par `unzip -tq`, version interne 4.3.4 confirmée, puis figée avec SHA-256 `9aad4e7b0bd0f35e3a918a0cf68a3dbfef473df09ca8e6b3a471bb4213e965d5`. | **PASS** ; checksum publié dans le dépôt, le bundle et la release. |
| **Majeur corrigé** | `scripts/ci-cold-acceptance.sh` | Les preuves froides étaient produites manuellement et ne constituaient pas un contrôle CI unique. | Orchestrateur strict : runtime vierge, installateur, serveur, routes, E2E, visuel, i18n, browser, tri, HMAC, SQL, Semgrep, JSON et seuils bloquants. | **PASS** localement sur port 8095 ; job CI configuré pour GitHub. |
| **Majeur corrigé** | `.github/workflows/cdc-v6.17.15.yml:45–75` | La CI précédente ne rejouait pas WordPress/MariaDB/HTTP/HMAC/SQL. | Nouveau job `cold-acceptance`, dépendant des contrats statiques, avec upload des preuves versionnées. | **Configuré et bloquant** ; le run GitHub du tag v6.17.15 doit confirmer son succès. |
| **Majeur corrigé** | `.github/workflows/cdc-v6.17.15.yml:77–105` | Le package pouvait être construit indépendamment d’une acceptance réelle. | `package.needs = [static-contracts, cold-acceptance]`, vérification Estatik, ZIP, manifestes et preuve senior. | **Configuré ; le run v6.17.12 a été bloqué en amont par son checksum, défaut corrigé dans v6.17.15.** |
| **Majeur corrigé** | `.github/workflows/cdc-v6.17.15.yml:107–124` | La création de release n’était pas conditionnée aux contrôles froids. | Job `release.needs = package`, déclenché uniquement par `refs/tags/v6.17.15`, avec checksum et `unzip -tq` avant `gh release create`. | **Configuré ; aucun contournement manuel prévu.** |
| **Moyen corrigé** | `scripts/package.sh:30–39, 151–160` | Le packageur ne garantissait pas la présence de l’artefact Estatik. | Copie de `vendor-artifacts/` dans le bundle et assertions obligatoires sur ZIP et checksum. | **PASS** par package local. |
| **Moyen corrigé** | `scripts/start.sh:28` | Le message affichait encore `admin/admin`. | Message aligné sur les variables `PK_ADMIN_USER` et `PK_ADMIN_PASS`. | **Corrigé.** |
| **Majeur corrigé après CI** | `.github/workflows/cdc-v6.17.12.yml:39,57` | Le premier tag v6.17.12 appelait `sha256sum -c` depuis la racine alors que le manifeste contenait un nom relatif à `vendor-artifacts/`. GitHub a bloqué `static-contracts` avant tout package/release. | v6.17.15 exécute `cd vendor-artifacts && sha256sum -c --strict estatik-4.3.4.zip.sha256` dans les jobs static et cold. | **Correction vérifiée localement ; v6.17.12 reste un tag immuable sans release.** |
| **Majeur corrigé après CI** | `scripts/install-tooling.sh:30–36` | Le job cold v6.17.13 supposait que `uv` était installé sur le runner GitHub ; GitHub a échoué avec `sudo: uv: command not found`. | Fallback portable vers `python3 -m pip install --user --break-system-packages`, ajout de `$HOME/.local/bin` au PATH, avec uv conservé lorsqu’il est disponible. | **Corrigé dans v6.17.15 ; acceptance locale réussie.** |
| **Majeur corrigé après CI** | `scripts/start.sh:9,24` et `scripts/ci-cold-acceptance.sh:25–35` | L’échec HTTP du run v6.17.14 ne conservait pas le journal du serveur PHP, ce qui limitait le diagnostic. | Le serveur écrit désormais dans un log versionné ; l’attente HTTP utilise des timeouts explicites et imprime ports, processus et dernières lignes du log en cas d’échec. L’upload des preuves est `always()`. | **Correction ajoutée dans v6.17.15.** |
| **Mineur résiduel** | Signature de l’artefact Estatik | Le SHA-256 garantit l’identité du fichier livré mais ne remplace pas une signature éditeur indépendante. | L’artefact est conservé avec provenance et checksum. Une signature cosignée pourra être ajoutée si Estatik en publie une. | **Risque résiduel faible et documenté.** |

## Résultats de la recette froide locale

| Contrôle | Résultat |
|---|---:|
| Installation WordPress froide avec archive Estatik embarquée | **PASS** |
| WordPress / PHP / MariaDB / WP-CLI | 7.1 / 8.3.6 / 10.11.14 / 2.12.0 |
| Estatik / Polylang / Query Monitor | 4.3.4 / 3.8.7 / 4.0.7 |
| Fixture | 30 propriétés publiées |
| Routes | **16/16** |
| E2E Playwright | **16/16** |
| Visuel | **30/30**, seuil 0,5 % |
| Browser/cookie/robots | **10/10** |
| Font arabe/RTL | **3/3** |
| Tri prix/surface | **3 ordres × 24 résultats** |
| HMAC | **5 rondes**, rejets négatifs `401` validés |
| SQL | **49 / 45 / 45**, seuil 56 |
| Semgrep | **0 finding bloquant** |
| JSON de preuves | `jq empty` sur les rapports v6.17.15 |
| Package | ZIP valide, artefact Estatik présent, checksum vérifié |

La recette locale a utilisé une base et un répertoire WordPress neufs, un mot de passe SQL contenant une apostrophe et des identifiants administrateur non codés en dur. Le résultat `COLD_ACCEPTANCE=PASS` a été obtenu avec `PK_PORT=8099` lors de la dernière exécution locale fonctionnelle ; v6.17.15 ajoute les diagnostics serveur et sera confirmé par le run GitHub du tag.

## Règle de publication

La branche et le tag ne doivent être publiés comme release qu’après succès des jobs dans cet ordre :

```text
static-contracts → cold-acceptance → package → release
```

Le fallback générique est volontairement refusé par défaut. Une exécution sans archive locale et sans couple `PK_ESTATIK_URL`/`PK_ESTATIK_SHA256` sort en code 2. Le seul contournement est `PK_ALLOW_UNPINNED_ESTATIK=1`, qui journalise explicitement `ESTATIK_REPRODUCIBLE=0` et ne doit jamais être utilisé dans le job `cold-acceptance`.

## Références

[1]: https://wordpress.org/plugins/estatik/ "Répertoire officiel Estatik WordPress.org : version et changelog 4.3.4"
[2]: https://downloads.wordpress.org/plugin/estatik.zip "Archive officielle Estatik utilisée pour créer l’artefact figé"
[3]: ../scripts/install.sh "Installation et vérification checksum Estatik"
[4]: ../scripts/ci-cold-acceptance.sh "Orchestrateur acceptance froide"
[5]: ../.github/workflows/cdc-v6.17.15.yml "Workflow CI statique, cold acceptance, package et release"
[6]: ../scripts/package.sh "Packageur et assertions de complétude du bundle"
