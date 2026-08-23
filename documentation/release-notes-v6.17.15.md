# Partikulier v6.17.15 — reproductibilité et acceptance froide

## Corrections

Cette version embarque l’archive Estatik 4.3.4 sous `vendor-artifacts/estatik-4.3.4.zip`, avec le checksum SHA-256 `9aad4e7b0bd0f35e3a918a0cf68a3dbfef473df09ca8e6b3a471bb4213e965d5`. L’installation refuse un artefact absent ou non vérifié. Une URL distante n’est admise qu’avec son checksum explicite.

Le nouveau workflow CI exécute un job `cold-acceptance` sur un environnement neuf. Il rejoue l’installation WordPress/MariaDB, les routes 16/16, l’E2E 16/16, le visuel 30/30, la négociation navigateur, les polices/RTL, le tri, l’HMAC, la mesure SQL et Semgrep. Le package dépend du succès de l’acceptance. La release dépend du package et ne peut être créée que sur le tag `v6.17.15` après vérification du ZIP et de son SHA-256.

Le fallback générique WordPress.org est conservé uniquement pour diagnostic local exceptionnel : il exige `PK_ALLOW_UNPINNED_ESTATIK=1`, journalise `ESTATIK_REPRODUCIBLE=0` et est interdit par la procédure CI/release.

## Correction issue de la CI

Le tag v6.17.12 a été bloqué par GitHub Actions avant package et release : son job statique exécutait le contrôle du checksum depuis la racine alors que le manifeste contenait le nom relatif `estatik-4.3.4.zip`. v6.17.15 corrige les deux occurrences avec `cd vendor-artifacts && sha256sum -c --strict estatik-4.3.4.zip.sha256`. Le tag v6.17.12 reste immuable et aucune release v6.17.12 n’a été créée.

## Validation locale

L’orchestrateur local a obtenu `COLD_ACCEPTANCE=PASS` sur une installation neuve, avec WordPress 7.1, PHP 8.3.6, MariaDB 10.11.14, WP-CLI 2.12.0, Estatik 4.3.4, Polylang 3.8.7, Query Monitor 4.0.7, Node 22.13.0, Playwright 1.62.1 et 30 propriétés publiées.

## Limite résiduelle

Le SHA-256 fige l’identité de l’archive Estatik utilisée mais ne constitue pas une signature indépendante de l’éditeur. La provenance et la limite sont documentées dans `documentation/estatik-artifact-v4.3.4.md`.
