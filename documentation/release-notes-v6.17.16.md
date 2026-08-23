# Partikulier v6.17.16 — candidate de clôture CDC fermé

## Corrections livrées

Cette version embarque l’archive Estatik 4.3.4 sous `vendor-artifacts/estatik-4.3.4.zip`, avec le checksum SHA-256 `9aad4e7b0bd0f35e3a918a0cf68a3dbfef473df09ca8e6b3a471bb4213e965d5`. L’installation refuse un artefact absent ou non vérifié. Une URL distante n’est admise qu’avec son checksum explicite.

Le workflow CI exécute `static-contracts`, puis `cold-acceptance`, `package` et enfin `release` uniquement pour le tag `v6.17.16`. L’acceptance froide repart d’un runtime et d’une base dédiés, refuse un port déjà occupé, réaligne `home`/`siteurl` et conserve les logs en cas d’échec.

Le wrapper Semgrep passe explicitement les 66 fichiers PHP au moteur et rapporte les chemins réellement présents dans `.paths.scanned`, ainsi que les octets et compteurs dérivés du JSON brut. Le ruleset structurel cible uniquement les trois classes d’automatisation ; les routes du dashboard propriétaire restent traitées selon leur modèle d’authentification nonce/cookie distinct de l’HMAC automation.

Les preuves de provenance exigent un SHA Git de 40 caractères. Les traces HMAC détaillent cinq rounds concurrents et quatre négatifs `401`, avec uniquement des SHA-256 d’événements et de bodies, sans secret ni identifiant brut. Le packageur ne met plus le nom de branche dans `INSTALL.md` ou le manifeste candidate : la reproductibilité est définie par commit.

## Validation locale

L’orchestrateur a produit localement les résultats suivants : routes 16/16, E2E 16/16, visuel 30/30, browser/cookie/robots 10/10, i18n/RTL 3/3, tri 3 ordres × 24, HMAC 5 rounds + 4 × 401, SQL 49/45/45 sous le seuil 56, et Semgrep 66/66 cibles avec 0 finding bloquant et 0 erreur.

## Condition de publication

La validation locale ne constitue pas le sign-off final. La release doit rester bloquée jusqu’au succès GitHub des quatre jobs, à la preuve `cmp` entre l’artifact package et l’asset ZIP téléchargé, à la vérification SHA-256 et à la revue indépendante. Le fallback Estatik générique exige `PK_ALLOW_UNPINNED_ESTATIK=1`, journalise son caractère non reproductible et est interdit par la CI/release.
