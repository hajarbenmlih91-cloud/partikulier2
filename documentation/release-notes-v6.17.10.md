# Partikulier 6.17.10 — livraison CDC fermé

Cette release livre la candidate corrigée sur la branche `fix/cdc-v6.17.10`, commit `f0d38d1`.

Le bundle principal `partikulier-6.17.10.zip` est autosuffisant : il contient le thème, le mu-plugin, les scripts racine, `tests/routes-contract.json`, les 30 baselines `tests/baselines-6.17.10/`, les preuves JSON/logs, le ruleset Semgrep, le workflow CI et les manifestes internes.

## Validation froide

| Contrôle | Résultat |
|---|---:|
| Routes HTTP directes | 16/16 |
| E2E frais | 16/16 |
| Visuel | 30/30 |
| Détection navigateur | 10/10 |
| Police Noto Sans Arabic / RTL | 3/3 |
| HMAC HTTP | 5 rondes, 200/200, 4 rejets 401 |
| SQL | 47, 43, 43 requêtes, seuil 56 |
| Semgrep | 66 cibles, 701 966 octets, 0 finding bloquant |
| Builds | byte-identiques après stabilisation des logs |

L’empreinte SHA-256 externe du bundle est livrée avec `partikulier-6.17.10.zip.sha256`. Le rapport complet et toutes les preuves sont aussi présents dans le bundle.
