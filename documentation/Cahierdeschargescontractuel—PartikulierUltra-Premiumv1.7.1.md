# Amendement contractuel — Partikulier Ultra-Premium v1.7.1

**Base :** `Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.md`.
**Objet :** fermer les ambiguïtés identifiées par la revue indépendante avant toute nouvelle candidate.

## A1 — Preuves volatiles et package déterministe

Le package déterministe contient uniquement les éléments nécessaires au produit, aux contrats et à la reproduction : code, scripts, contrats, baselines approuvées, artefacts vendor et manifeste d’entrées. Les éléments volatils `run_id`, `source_ref`, timestamps, runner, CPU, mémoire observée et logs de processus sont publiés comme sidecars CI/release.

Si une preuve CI est incluse dans le bundle de revue, elle ne contribue pas aux octets du package déterministe ou elle doit être produite avec une règle de normalisation explicitement versionnée. Les champs présents dans le ZIP qui déterminent son SHA utilisent uniquement la version, le commit, les hashes d’entrées et des valeurs déterministes. `source_ref`, `run_id`, timestamps, runner, CPU, mémoire observée et logs ne doivent jamais modifier les octets du ZIP reproductible ; ils restent dans les preuves CI/release sidecar. `PACKAGE_REPRODUCIBLE` et `SOURCE_TO_RELEASE_REPRODUCIBLE` sont deux statuts distincts.

## A2 — Interdiction des statuts incomplets pour M0/M1

Pour une exigence M0 ou M1 obligatoire, `NOT_COVERED`, `NOT_RUN`, `SKIPPED`, `NO_BASELINE`, `NON_REPRODUCIBLE` et `BLOCKED` valent échec de la gate. `NOT_COVERED` est réservé à une capacité M2 ou à une exclusion approuvée dans la matrice. Le mot `PARTIAL` n’est pas un statut de test ou de décision ; une implémentation incomplète doit être séparée en `IMPLEMENTED`/`NOT_IMPLEMENTED` et un test doit avoir son propre statut.

## A3 — Périmètre core/pro

`partikulier-core` est M0 et doit être présent, installable, activé, migré et testé. `partikulier-pro` est M2 par défaut et est explicitement hors périmètre de la première release technique tant qu’il n’est pas implémenté. Paiement, commandes, webhooks commerciaux, alertes, jobs et autres capacités optionnelles sont classés individuellement dans `scope-matrix.csv`; leur présence dans l’arborescence ne constitue pas une preuve de livraison. La matrice doit préciser pour chaque capacité si elle contribue ou non à la première release.

## A4 — Budgets SQL immuables

Les budgets sont versionnés dans `documentation/capacity-envelope.json` avant le lancement de la recette. Pour 30 annonces, la référence est ≤56 requêtes à froid et ≤40 à chaud, mesurées par `SAVEQUERIES` sur le périmètre du template d’archive déclaré. Pour 1 000 annonces, la référence est ≤120 à froid et ≤80 à chaud. La fixture, les requêtes incluses/exclues, le cache et le nombre d’itérations sont immuables pour la candidate. Un dépassement est `FAIL`.

## A5 — Charge et mémoire

La charge décrit la machine, OS, CPU, mémoire, cgroup, stockage, réseau, PHP-FPM, MariaDB, workers et cache. La métrique mémoire de référence est le pic RSS du cgroup du job, avec seuil, intervalle et comportement OOM documentés. Les quatre workers PHP sont une configuration de référence fixe. Les résultats publient p50, p95, p99, erreurs, débit, CPU, mémoire, SQL, minimum, maximum et point de saturation.

## A6 — Scénarios visuels figés

Les 30 scénarios sont identifiés dans le contrat : `home`, `archive`, `single`, `deposer`, `favoris`, chacun en FR/EN/AR et desktop/mobile. Chaque entrée fixe viewport, langue, état connecté/non connecté, fixture, URL directe, résultat attendu et baseline. La CI ne régénère jamais les baselines après un échec. Toute rebaseline exige l’ancien et le nouveau hash, les captures avant/après, la cause et un approbateur indépendant.

## A7 — `N/A` et `NOT_APPLICABLE`

Le littéral `N/A` est interdit comme valeur vague dans les rapports finaux. `NOT_APPLICABLE` est accepté uniquement lorsqu’une exigence est réellement hors périmètre, avec justification, impact, durée, approbation et ligne correspondante dans `scope-matrix.csv`. Il ne peut jamais remplacer `NOT_RUN` pour une gate obligatoire.

## A8 — Dépendances externes

Toute dépendance téléchargée ou exécutée est enregistrée dans `documentation/dependency-manifest.json` avec nom, version, source, archive, SHA-256, licence, date de récupération et comportement si la source disparaît. Cela couvre WordPress, Estatik, Polylang, Query Monitor, dépendances Node, Chromium et services tiers pertinents. La CI vérifie les archives vendor, le lockfile, le SBOM et le scan des secrets. Une source absente ou une checksum divergente bloque l’installation reproductible.

## A9 — Approbations et signature

Une approbation finale indique identité ou rôle, conflit d’intérêts, périmètre, version, commit, SHA de l’asset, date UTC, preuves examinées, réserves, décision et signature. Une approbation GitHub protégée, une signature électronique ou une signature cryptographique sont acceptables uniquement si leur méthode et leur vérification sont documentées. Un tag `unsigned` ne doit jamais être présenté comme signé.

## A10 — Critère de label

`TECHNICAL_RELEASE = PASS` exige toutes les gates M0, la cohérence commit–preuves–asset et le statut `SOURCE_TO_RELEASE_REPRODUCIBLE` si celui-ci est annoncé. `UX_CONTENT = PASS` exige la revue UX et les attestations natives. `COMMERCIAL_RELEASE = PASS` exige la décision produit/client. `ULTRA_PREMIUM = PASS` exige les trois décisions, la charge 1 000 annonces, les opérations backup/restore/rollback et l’absence de réserve bloquante.

Tant qu’une de ces conditions manque, la seule formulation autorisée est **RELEASE CANDIDATE** ou **CONFORME TECHNIQUEMENT SOUS RÉSERVES**.
