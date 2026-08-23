# Revue senior — Partikulier v6.17.16

**Statut : candidate de clôture technique, avant vérification GitHub et approbation indépendante.**

Cette candidate corrige les quatre écarts de traçabilité relevés lors des audits indépendants de v6.17.10 à v6.17.15. Les tags et releases historiques ne sont pas réécrits.

| Écart audité | Correction v6.17.16 | Preuve attendue | Statut local |
|---|---|---|---|
| Provenance `uncommitted` ou synthétique | Les suites exigent un SHA de 40 caractères ; `stamp-provenance.sh` estampille les JSON avec ce SHA et refuse les placeholders. | JSON v6.17.16 et log de provenance | PASS |
| Semgrep annonçant une portée différente du moteur | Le wrapper fournit explicitement les 66 fichiers PHP à Semgrep et dérive les cibles, octets et compteurs de `.paths.scanned` du JSON brut. | `documentation/semgrep-v6.17.16.json` | PASS : 66 cibles, 0 finding bloquant, 0 erreur |
| REST direct faussement signalé ou silencieusement exclu | Le ruleset cible structurellement les trois classes d’automatisation ; les routes propriétaires d’`Owner Insights`, protégées par nonce/cookie et distinctes de l’automation HMAC, ne sont pas requalifiées à tort. | Ruleset et rapport Semgrep | PASS |
| Package dépendant de la branche | `INSTALL.md` et le manifeste candidate ne contiennent plus le nom de branche ; la provenance est commit-only. | Deux builds depuis branche et checkout détaché du même commit | À confirmer par le job package |
| Preuve HMAC trop compacte | Chaque round expose codes HTTP, résultats duplicate et SHA-256 non secrets de l’événement et du body ; les quatre négatifs sont détaillés. | `documentation/hmac-http-v6.17.16.json` | PASS : 5 rounds, 4 × 401, secret absent |
| Faux cold run sur serveur ou base existants | Le port occupé est maintenant bloquant ; l’acceptance supprime la base dédiée avant installation et réaligne `home`/`siteurl`. | Log d’acceptance froide | PASS local |

## Résultats observés localement

| Contrôle | Résultat |
|---|---:|
| Installation WordPress/MariaDB froide | PASS |
| Contrat routes | 16/16 |
| Parcours E2E | 16/16 |
| Visuel | 30/30, seuil 0,5 % |
| Browser/cookie/robots | 10/10 |
| i18n et polices RTL | 3/3 |
| Tri | 3 ordres × 24 résultats |
| HMAC HTTP | 5 rounds concurrents + 4 rejets 401 |
| SQL | 49 / 45 / 45, seuil 56 |
| Semgrep | 66 cibles brutes, 3 règles, 0 finding, 0 erreur |
| Versions du thème | 4 fichiers concordants en 6.17.16 |

La réussite locale ne vaut pas à elle seule sign-off CDC. La publication est autorisée techniquement uniquement si le workflow GitHub versionné exécute, dans l’ordre, `static-contracts → cold-acceptance → package → release`, si le ZIP de release est byte-identique à l’artifact package et si son SHA-256 est vérifié après téléchargement.

> **Conclusion senior :** les corrections de code et de preuve sont prêtes pour la vérification GitHub. Tant que le run de la candidate v6.17.16 et la comparaison de l’asset publié ne sont pas constatés, `LABEL_AUTORISÉ = NON` et `APPROBATION_INDÉPENDANTE = EN ATTENTE`.

## Références internes

[1]: ../scripts/stamp-provenance.sh
[2]: ../scripts/run-semgrep-v6.17.16.sh
[3]: ../scripts/test-hmac-http.sh
[4]: ../scripts/package.sh
[5]: ../scripts/ci-cold-acceptance.sh
[6]: ../.github/workflows/cdc-v6.17.16.yml
