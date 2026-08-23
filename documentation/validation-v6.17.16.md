# Validation CDC fermé — Partikulier v6.17.16

**Nature du document :** rapport de candidate, non sign-off final.
**Provenance :** les preuves CI sont restampillées avec le SHA exact du checkout du tag ; aucun identifiant de branche ou placeholder n’est une preuve de release.

## Tableau de clôture

| Niveau | Exigence | Preuve v6.17.16 | Statut avant CI GitHub |
|---|---|---|---|
| M0 | Tag immuable sur un commit complet de 40 caractères | Tag à créer après commit final ; aucun tag historique modifié | EN ATTENTE |
| M0 | Chaîne bloquante `static-contracts → cold-acceptance → package → release` | `.github/workflows/cdc-v6.17.16.yml` | EN ATTENTE du run GitHub |
| M0 | ZIP release égal bit-à-bit à l’artifact package et SHA vérifié | Assertions package/release du workflow | EN ATTENTE |
| M0 | Bundle complet et preuves archivées | `scripts/package.sh`, preuves JSON/logs, baselines, workflow, ruleset, artefact Estatik | PASS local, CI à confirmer |
| M1 | Provenance sans `uncommitted` ni placeholder | SHA strict dans les suites et `stamp-provenance.sh` | PASS local |
| M1 | Semgrep exact et vérifiable | 66 cibles brutes, 3 règles, 0 finding bloquant, 0 erreur | PASS local |
| M1 | HMAC HTTP détaillé | 5 rounds concurrents, 4 réponses `401`, détails SHA-256 non secrets | PASS local |
| M1 | Package indépendant de la branche | Métadonnées commit-only ; comparaison branche/détaché à produire | EN ATTENTE du job package |
| M2 | Approbation indépendante | Décision d’un reviewer humain séparé de l’auteur | EN ATTENTE, non automatisable |

## Résultats locaux disponibles

| Suite | Résultat |
|---|---:|
| Installation froide | PASS |
| Routes | 16/16 |
| E2E | 16/16 |
| Visuel | 30/30 |
| Browser/cookie/robots | 10/10 |
| i18n/RTL | 3/3 |
| Tri | 3 × 24 |
| HMAC | 5 rounds + 4 × 401 |
| SQL | 49 / 45 / 45, seuil 56 |
| Semgrep | 66/66, 0 finding, 0 erreur |

> **LABEL_AUTORISÉ = NON** tant que le tag v6.17.16, le run GitHub complet, l’égalité byte-à-byte package/release et la vérification indépendante ne sont pas documentés avec leurs identifiants exacts.

> **APPROBATION_INDÉPENDANTE = EN ATTENTE.** Elle doit être donnée par un reviewer humain externe au présent travail ; elle ne peut pas être déduite d’un test automatisé ni fabriquée dans ce rapport.

## Procédure de clôture

Après commit, le dépôt doit être poussé sur la branche de correction, puis le tag immuable v6.17.16 doit pointer vers le SHA exact. Le run associé au tag doit être vert sur les quatre jobs. L’asset ZIP doit ensuite être téléchargé depuis GitHub, contrôlé par `unzip -tq`, comparé par `cmp` à l’artifact package et haché par SHA-256. Le reviewer indépendant consigne enfin son approbation séparément.
