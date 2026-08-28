# Passation développeur — clôture Partikulier

**Date :** 2026-08-28 UTC
**Dépôt public :** https://github.com/hajarbenmlih91-cloud/partikulier2
**Branche de travail :** `automation/capacity-apcu-a58942c`
**Dernier SHA poussé :** `e488b12bc518922f8148a859ce8835e643267700`
**Verdict actuel :** **NO-GO**

## 1. Résumé exécutable

Le dépôt contient le thème, les tests, les contrats documentaires et les scripts de validation. Le dernier commit `e488b12` est contractuel/documentaire : il réduit le contrat des gates ouverts à O1–O18, aligne le validateur sur les exit codes `0/1/2/75/77` et ne modifie aucun fichier public du thème. Il ne constitue pas une correction fonctionnelle ni un GO.

La CI exacte du SHA `e488b12` est le run [33140371484](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/33140371484). Le job statique est PASS. Le cold acceptance est FAIL avec exit `1` à cause du pixel visual. Le package n’a pas été généré dans ce run, car le job package dépend du cold acceptance.

## 2. Preuves du run exact

Le dossier cold versionné sous `documentation/evidence/ci-33140371484/` doit rester associé exclusivement au SHA `e488b12`. Il contient l’archive officielle du cold acceptance et le rapport de statut. L’archive ne doit jamais être interprétée comme preuve du staging.

| Job ou contrôle | Résultat exact |
|---|---|
| Static contracts et validateur | PASS |
| UI Chromium / Firefox / WebKit | PASS, 30/30 par moteur sur le contrôle UI |
| Capacity | PASS, exit `0` |
| Upgrade | PASS, exit `0` |
| Visual pixel | FAIL, `26/30`, `VISUAL_EXIT=1` |
| Package | Non exécuté après échec cold acceptance |

Les quatre scénarios pixel en échec sont `archive-ar-desktop` à `2,2919 %`, `archive-ar-mobile` à `2,9357 %`, `single-ar-desktop` à `11,0918 %` et `single-ar-mobile` à `14,9053 %`, pour un seuil de `0,5 %`. `single-fr-desktop` est PASS dans ce run exact. Aucun CSS, template, asset ou baseline ne doit être modifié pour masquer ces différences.

## 3. Règles impératives

La production est strictement interdite. Le staging Hostinger est volontairement conservé sur `a58942c`; aucune tentative Hostinger, Cloudflare, hPanel, File Manager, FTP, purge ou déploiement ne doit être menée. La CI locale ou GitHub Actions ne prouve pas l’état du staging.

La fixture synthétique propriété `249` et le média `250` restent publiés jusqu’à une autorisation explicite de suppression. Ne pas créer de fixture EN/AR, ne pas modifier les chaînes Polylang réelles, ne pas supprimer de données métier et ne pas demander de credentials, cookies, tokens, `wp-config.php`, dumps SQL ou secrets.

Aucune modification de design, UX, CSS, template, asset, texte public, traduction, RTL, barre sticky ou responsive n’est autorisée pour faire passer un gate. Toute correction fonctionnelle doit être précédée d’une cause prouvée et d’un test avant/après.

## 4. Objectifs encore ouverts

Le contrat machine-readable est `documentation/objectives-contract-v1.0.json`; il contient O1–O18. La correspondance détaillée avec l’ancien contrat O1–O22 est dans `documentation/evidence/ci-33140371484/o1-o22-mapping-e488b12.md`. Cette correspondance est une proposition de regroupement; elle ne doit pas être considérée approuvée sans validation de périmètre.

| Objectif | Preuve attendue | État réel |
|---|---|---|
| O1 alignement CI/staging | Provenance vérifiable du SHA installé | BLOCKED/NO-GO; staging sur `a58942c` |
| O2 périmètre du diff | Diff depuis `a58942c`, revue et hashes | Partiellement documenté; aucun fichier public modifié dans `e488b12` |
| O3 REST portée sécurité | 10/10 scénarios | PASS historique sur SHA antérieur; à rattacher au SHA utilisé |
| O4 REST équivalence métier | 100 % champs comparés | Preuve disposable 19/19 sur SHA historique; pas une preuve staging |
| O5 archives métier | HTML vente/location/action inconnue | Location HTML non suffisamment prouvée; NO-GO |
| O6 langues FR/EN/AR | Résultats localisés complets | Fixture EN/AR non autorisée; NO-GO |
| O7 TTFB | p95 <= 800 ms, 20 mesures par route et source | FAIL sur staging; mesures largement supérieures |
| O8 attribution TTFB | Chaîne client/TLS/HCDN/proxy/origine prouvée | BLOCKED; diagnostics Hostinger indisponibles |
| O9 capacity | nominal, write, burst et seuils CPU/latence | PASS dans CI précédente/exacte selon le rapport, mais ne compense aucun autre gate |
| O10 visual | 30/30, diff <= 0,5 % | FAIL sur `e488b12`: 26/30 |
| O11 baselines | 30 PNG + SHA256SUMS | PASS historique; conserver le SHA de preuve |
| O12 CI finale | Tous jobs requis PASS, aucun SKIPPED | FAIL sur `e488b12` car cold échoue |
| O13 package | ZIP CI = rebuild détaché | Non exécuté sur `e488b12`; package antérieur ne certifie pas ce SHA |
| O14 upgrade | Toutes assertions avant/après | PASS dans le cold exact |
| O15 rollback | Rollback final + idempotence | Pas suffisamment prouvé comme rollback du package final |
| O16 revue visuelle humaine | 1 signoff indépendant | MISSING / NOT AUTHORIZED |
| O17 nettoyage staging | Autorisation puis suppression 249/250 et absence publique | NOT AUTHORIZED; ne pas supprimer |
| O18 signoffs finaux | 8 signoffs nommés et datés | MISSING / NOT AUTHORIZED |

## 5. Reprise technique recommandée

Commencer par reproduire les quatre scénarios arabes en pixel dans le même runtime CI, trois fois chacun, avec seed, URL, viewport, métadonnées galerie et médias identiques. Comparer les captures et les DOM; établir si la divergence vient des données, de la galerie, des assets, des polices, du runtime ou du code. Ne pas rebaseliner sans cause prouvée et revue indépendante.

Ensuite, si et seulement si une correction fonctionnelle est prouvée, créer un nouveau commit fonctionnel séparé du contrat documentaire. Rejouer la CI sur le nouveau SHA exact. Le package officiel ne sera produit que si le cold acceptance passe. Télécharger l’archive package et vérifier la comparaison détachée avant toute déclaration de PASS.

Pour les objectifs staging et TTFB, le seul chemin autorisé par le périmètre actuel est de conserver les statuts BLOCKED/NO-GO, car l’accès Hostinger/Cloudflare a été abandonné. Ne pas contourner cette décision et ne pas présenter une mesure locale comme une preuve live.

## 6. Commandes de reprise

```bash
gh repo clone hajarbenmlih91-cloud/partikulier2
cd partikulier2
git checkout automation/capacity-apcu-a58942c
git rev-parse HEAD
python3 scripts/validate-objectives-v1.0.py documentation/objectives-contract-v1.0.json
python3 -m json.tool documentation/objectives-contract-v1.0.json >/dev/null
git diff --check
```

Pour une nouvelle qualification CI, utiliser uniquement un SHA poussé et noter l’URL du run, les jobs, les exit codes, les artefacts, la date UTC et le SHA vérifié. Ne pas utiliser `git add .`; ajouter uniquement les fichiers explicitement relus et approuvés.

## 7. Définition du GO

Le GO est interdit tant que chaque objectif obligatoire n’est pas PASS avec exit `0`, que le SHA CI n’est pas celui du staging, que le TTFB n’est pas sous `800 ms` avec attribution complète, que les archives métier et langues requises ne sont pas prouvées, que le rollback final n’est pas exécuté, et que les signoffs humains ne sont pas signés.

**Un commit documentaire, un validateur vert ou une CI du parent ne ferme aucun objectif technique à lui seul.**

## Références

[1]: https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/33140371484 "CI exacte du SHA e488b12"
[2]: https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/capacity-apcu-a58942c/documentation "Documentation versionnée"
