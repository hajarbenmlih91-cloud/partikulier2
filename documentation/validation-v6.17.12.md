# Validation de la candidate v6.17.12

La candidate v6.17.12 est dérivée de `v6.17.11@e1b59edf0c8f6b5bc15509c86a86bf2772159087` et comparée à la base CDC `v6.17.7@6153debac8f84da46b1da95af1c810320dc7e5bf`. Les tags v6.17.7 et v6.17.11 ne sont pas réécrits.

## Contrôles

| Contrôle | Résultat |
|---|---:|
| Installation froide avec archive Estatik embarquée | PASS |
| WordPress / PHP / MariaDB / WP-CLI | 7.1 / 8.3.6 / 10.11.14 / 2.12.0 |
| Estatik / Polylang / Query Monitor | 4.3.4 / 3.8.7 / 4.0.7 |
| Fixture properties publiées | 30 |
| Routes contractuelles | 16/16 |
| E2E Playwright | 16/16 |
| Régression visuelle | 30/30, seuil 0,5 % |
| Browser/cookie/robots | 10/10 |
| Font arabe/RTL | 3/3 |
| Famille i18n | FR/EN/AR non vide |
| Tri prix/surface | 3 ordres × 24 résultats |
| HMAC HTTP | 5 rondes, rejets négatifs 401 |
| SQL archive | 49 / 45 / 45, seuil 56 |
| Semgrep | 0 finding bloquant |
| PHP/JS | 66 PHP et 15 JavaScript sans erreur |
| Package | ZIP valide, artefact Estatik et checksum présents |

La recette locale a été orchestrée par `scripts/ci-cold-acceptance.sh` sur un répertoire WordPress et une base neufs, avec mot de passe SQL contenant une apostrophe. Le résultat observé est `COLD_ACCEPTANCE=PASS` sur `http://localhost:8095`.

## Artefact Estatik

L’archive `vendor-artifacts/estatik-4.3.4.zip` est la copie inspectée de la source officielle WordPress.org. Son fichier principal déclare la version 4.3.4 et son SHA-256 est `9aad4e7b0bd0f35e3a918a0cf68a3dbfef473df09ca8e6b3a471bb4213e965d5`. L’installateur exécute `sha256sum --check --strict` avant `wp plugin install`.

Le mode par défaut est reproductible. Une URL externe n’est acceptée qu’avec `PK_ESTATIK_URL` et `PK_ESTATIK_SHA256`. Le fallback générique sans checksum exige `PK_ALLOW_UNPINNED_ESTATIK=1`, journalise l’état non reproductible et n’est pas utilisé par la CI.

## Chaîne CI et release

Le workflow `.github/workflows/cdc-v6.17.12.yml` impose l’ordre `static-contracts → cold-acceptance → package → release`. Le job `cold-acceptance` reconstruit l’environnement et publie ses preuves. Le job `package` dépend de l’acceptance et vérifie le bundle. Le job `release` dépend du package, est limité au tag `v6.17.12`, vérifie le checksum du ZIP et ne crée la release qu’après succès des étapes précédentes.

La signature indépendante d’un éditeur n’est pas remplacée par un checksum SHA-256. Une signature cosignée pourra être ajoutée si elle est publiée par Estatik ; l’identité de l’artefact utilisé par cette candidate est néanmoins figée et vérifiable.
