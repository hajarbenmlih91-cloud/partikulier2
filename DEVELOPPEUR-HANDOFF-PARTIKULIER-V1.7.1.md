# Passation développeur — Partikulier / CDC v1.7.1

**Objectif :** permettre à un développeur de reprendre et de terminer le chantier Partikulier sans dépendre d’un contexte oral ou d’un agent précédent.
**Langue de travail :** français.
**Périmètre autorisé :** staging WordPress uniquement ; ne jamais modifier la production.
**Contrainte impérative :** terminer les gates techniques **sans modifier le design/UX validé**.

> **Règle de vérité :** ne jamais transformer un test non exécuté, bloqué par l’infrastructure, incomplet ou dépendant d’une validation humaine en PASS. Le verdict de départ est **NO-GO**.

## 1. Point de départ officiel

Le dépôt de référence est [partikulier2](https://github.com/hajarbenmlih91-cloud/partikulier2). La branche de travail est [`automation/release-approval-gate-v1.7.1`](https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1). Le dernier candidat source publié est le commit [`2ff8e38`](https://github.com/hajarbenmlih91-cloud/partikulier2/tree/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733).

| Référence | Rôle | Lien |
|---|---|---|
| `2ff8e38` | Candidat actuel : bootstrap public différé, durcissement filtre location, corrections type/sécurité, harnais honnête | [Voir le commit](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733) |
| `a8fd5ec` | Candidat précédent : compatibilité PHP CLI et lazy REST/theme | [Voir le commit](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/a8fd5ec715118f2a974e352c4299f62c428e6fb0) |
| `04906d7` | Première optimisation lazy REST/theme | [Voir le commit](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/04906d7) |
| `e675a91` | Initialisation REST programmatique | [Voir le commit](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/e675a91) |
| `d33e988` | Rapport historique final30 NO-GO à conserver | [Voir le commit](https://github.com/hajarbenmlih91-cloud/partikulier2/commit/d33e988) |

Pour commencer, cloner le dépôt puis vérifier la branche et le SHA avant toute modification :

```bash
gh repo clone hajarbenmlih91-cloud/partikulier2
cd partikulier2
git checkout automation/release-approval-gate-v1.7.1
git rev-parse HEAD
git status --short
```

Le dépôt contient de nombreux anciens journaux et artefacts non suivis issus des audits précédents. **Ne jamais exécuter `git add .`**. Toujours sélectionner les fichiers à ajouter explicitement et vérifier `git diff --cached --check` avant chaque commit.

## 2. Staging et sécurité opérationnelle

Le WordPress de test est accessible à [https://blanchedalmond-reindeer-376379.hostingersite.com](https://blanchedalmond-reindeer-376379.hostingersite.com). L’administration est [https://blanchedalmond-reindeer-376379.hostingersite.com/wp-admin/](https://blanchedalmond-reindeer-376379.hostingersite.com/wp-admin/). Le staging contient les plugins suivants : Estatik 4.3.4, LiteSpeed Cache 7.9, Partikulier Core 1.0.0 et Polylang 3.8.7.

Le thème actif est **Partikulier CDC v1.8 — final30**, version 6.17.22. Les contrôles précédents ont confirmé la classe `wp-theme-partikulier-cdc-v18-final30`, 21 cartes dans les archives FR/EN/AR, une carte par ligne sur mobile, les images responsives, la popup de filtre mobile, la navigation sticky, le RTL arabe et la présence des chambres/prix.

Au dernier passage, Hostinger demandait une reCAPTCHA Enterprise interactive dans `/wp-admin/`. Aucun contournement ne doit être tenté. Le nouveau commit `2ff8e38` est donc publié sur GitHub mais son activation dans WordPress n’est pas confirmée. Le core précédemment déployé et le thème final30 restent en place. Cette situation est documentée dans [staging-admin-access-2026-08-26.txt](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/release-approval-gate-v1.7.1/documentation/staging-admin-access-2026-08-26.txt), si le fichier est présent sur la branche de reprise.

Aucune donnée de production, aucun mot de passe, cookie, `wp-config.php`, dump SQL ou clé n’est inclus dans le dépôt. Ne jamais publier de credentials ni de données de session.

## 3. Design verrouillé : ce qui ne doit pas changer

Le développeur peut modifier le serveur, le core métier, les tests, le packaging, la configuration d’infrastructure et les scripts de preuve. Il ne doit pas modifier l’apparence pour faire passer un gate.

| Élément verrouillé | Exigence à préserver |
|---|---|
| Grille desktop | Deux cartes par ligne |
| Grille mobile | Une carte par ligne, sans overflow |
| Filtres mobile | Bouton et popup de filtres existants, avec fermeture Échap et retour du focus |
| Navigation | Navigation sticky validée, sans remplacement par une autre barre |
| Cartes | Structure, prix, chambres, badges, boutons et hiérarchie existants |
| Arabe | RTL, police actuelle, textes traduits existants et structure de la home |
| Assets | CSS, JS visuel, images, fonts et templates ne doivent pas être modifiés sauf correction fonctionnelle explicitement prouvée |
| Contenu | Ne pas traduire, supprimer, renommer ou recréer les annonces réelles |

Toute correction d’un lien, d’un filtre ou d’un bootstrap qui ne modifie pas le rendu est autorisée, mais elle doit être accompagnée d’un test avant/après et d’une preuve d’absence de régression visuelle.

## 4. Ce qui a déjà été réellement corrigé

Le fichier [`partikulier-core/partikulier-core.php`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/partikulier-core/partikulier-core.php) ne charge plus le `JobRunner` et ne programme plus ses cron jobs sur les pages publiques. Les classes REST et d’écriture restent chargées pour REST, CLI, administration et cron.

Le contrôleur [`RestController.php`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/partikulier-core/src/RestController.php) instancie ses services à la demande. Le repository [`ListingRepository.php`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/partikulier-core/src/ListingRepository.php) conserve les index adaptés et une invalidation APCu compatible avec les types réels. Le [`RateLimiter.php`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/partikulier-core/src/RateLimiter.php) conserve ses protections et assainit explicitement l’adresse client.

Le filtre [`class-search-filters.php`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/theme/inc/class-search-filters.php) utilise `es_status` comme taxonomie transactionnelle. Si `a-vendre` ou `a-louer` est demandé mais qu’aucun terme transactionnel ne peut être résolu, il renvoie zéro résultat au lieu de renvoyer silencieusement toutes les ventes.

Le harnais [`verify-live-final30-smoke.mjs`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/scripts/verify-live-final30-smoke.mjs) distingue désormais `PASS`, `FAIL` et `BLOCKED`. Une URL `a-louer` qui répond HTTP 200 mais ne contient aucune location n’est plus déclarée PASS.

## 5. Résultat CI officiel du candidat actuel

Le run push officiel est [`32972176681`](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32972176681). Les gates statiques ont réussi en 56 secondes. Le job froid a terminé en échec après environ 59 minutes, uniquement parce que le gate capacity n’a pas atteint l’enveloppe contractuelle.

| Gate | Résultat du candidat `2ff8e38` |
|---|---|
| Contrat core | **PASS : 8/8** |
| Contrat services | **PASS : 7/7** |
| Contrat thème | **PASS : 6/6** |
| Routes | **PASS : 16/16** |
| Parcours | **PASS : 16/16** |
| Accessibilité automatisée froide | **PASS : 6/6** |
| Charge HTTP | **PASS** |
| HMAC | **PASS** |
| SQL | **PASS**, sous le seuil configuré |
| Semgrep | **PASS : 0 finding bloquant sur 66 cibles** |
| Upgrade v6.17.16 → v6.17.22 | **PASS technique** |
| Baselines visuelles | **BLOCKED**, manifest absent |
| Capacity burst | **FAIL** |
| Capacity saturation | **FAIL** |

La preuve détaillée est dans [capacity-envelope-2ff8e38-2026-08-26.json](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/release-approval-gate-v1.7.1/documentation/capacity-envelope-2ff8e38-2026-08-26.json) si elle a été publiée, et dans le workflow ci-dessus.

## 6. Bloqueur capacity : mesures exactes et interprétation

Le test contractuel est défini par [`test-capacity-envelope-v1.7.1.py`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/scripts/test-capacity-envelope-v1.7.1.py). Il ne faut ni réduire les RPS, ni réduire la durée, ni supprimer la télémétrie CPU/RSS.

| Phase | Résultat |
|---|---:|
| Sustained read 10 RPS / 900 s | **PASS** : 10,000 RPS, p95 0,107 s, p99 0,115 s |
| Burst read 25 RPS / 60 s | **FAIL** : 24,033 RPS, p95 1,133 s, p99 1,153 s, CPU moyen 91,995 % |
| Write API 2 RPS / 900 s | **PASS** : 2,000 RPS, p95 0,107 s, p99 0,110 s |
| 50 sessions concurrentes | **PASS** : 50 requêtes, zéro erreur |
| Saturation probe 50 RPS / 10 s | **FAIL** : 28,1 RPS, p95 2,160 s, p99 2,172 s, CPU moyen 91,41 % |

La comparaison avec `a8fd5ec` montre une amélioration réelle du burst de 22,817 à 24,033 RPS, mais le seuil contractuel effectif d’au moins 24,75 RPS n’est toujours pas atteint. Le runtime de référence utilise quatre workers PHP-FPM ; APCu n’y est pas installé, donc le rate limiter public utilise le fallback WordPress Transient. Ce point doit être profilé, pas simplement supposé corrigé.

Le fichier [`start-reference-web.sh`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/scripts/start-reference-web.sh) décrit le runtime borné. Toute modification du nombre de workers ou ajout d’extension doit être documenté comme changement de runtime et comparé à l’environnement Hostinger réel. Il est interdit de modifier le test pour le faire passer artificiellement.

## 7. TTFB et infrastructure Hostinger

Les optimisations REST ont réduit le TTFB REST à environ 1,44–1,75 seconde dans une série, mais le front reste environ à 3,10–5,48 secondes. La cible contractuelle est `<800 ms`, donc le gate est **FAIL**.

Les réponses Hostinger utilisent `server: hcdn`, `platform: hostinger`, avec des états HCDN HIT/MISS et LiteSpeed hit, sans conformité stable au seuil. Le hPanel était inexploitable dans la session précédente. Le développeur doit obtenir un accès Hostinger/hPanel autorisé et vérifier le cache serveur/CDN, la configuration PHP-FPM, les règles d’exclusion et l’origine upstream.

Ne pas installer WP Super Cache en parallèle de LiteSpeed Cache : ce test a déjà été fait, sans header WP Super Cache ni gain TTFB, puis le plugin a été désactivé et supprimé. Ne pas activer QUIC.cloud, un CDN externe ou une nouvelle route de cache sans analyse de sécurité, réversibilité et mesure avant/après. Les routes authentifiées, privées, REST d’écriture et soumission ne doivent jamais être mises en cache publiquement.

## 8. Location : preuve à compléter proprement

Le statut transactionnel canonique est `es_status`. La vente est démontrée. La requête publique `?es_action=a-louer` répond HTTP 200, mais le dataset live contient actuellement 21 cartes, 20 badges vente et 0 badge location. Cette situation est **BLOCKED**, pas PASS.

Pour fermer ce gate, créer exclusivement sur le staging une fixture clairement nommée, par exemple `CDC TEST — LOCATION — NE PAS CONSERVER`, avec un identifiant de traçabilité dans le titre ou les métadonnées. Lui affecter `es_status=a-louer`, conserver l’ID et les relations Polylang éventuellement créées, puis tester le filtre, le badge, la carte, l’URL et le JSON-LD dans les langues nécessaires. La fixture doit être supprimée ou archivée immédiatement après les tests, avec un journal indiquant l’ID, les relations et la date de nettoyage. Ne jamais supprimer une annonce réelle ni casser une chaîne Polylang existante.

Si l’administration Hostinger reste bloquée par reCAPTCHA, demander un accès navigateur réellement autorisé ou un accès WordPress admin non bloqué. Ne pas contourner la reCAPTCHA et ne pas inventer la preuve location.

## 9. Navigateurs et harnais

Les retests live honnêtes ont donné les résultats suivants :

| Moteur | Résultat |
|---|---|
| Firefox | 6 contrôles techniques PASS ; location BLOCKED dataset ; 21 images valides à 320/360/375/390 px ; aucun overflow |
| WebKit | 6 contrôles techniques PASS ; location BLOCKED dataset ; 21 images valides à 320/360/375/390 px ; aucun overflow |
| Chromium | Smoke technique historique PASS ; relancer avec le harnais honnête si nécessaire |

Le CI UI froid a obtenu 24/30 dans chaque navigateur. Les six échecs sont `localized-link-crawl-invalid` et concernent uniquement le logo sur les scénarios FR archive, single et favoris : le lien pointe vers `/` au lieu de `/fr/`. Les liens répondent HTTP 200, mais le contrat de localisation les considère incorrects. Corriger ce lien fonctionnel côté URL/localisation sans changer son apparence, puis relancer les 30 scénarios dans Chromium, Firefox et WebKit.

Les rapports de retest peuvent être consultés dans les fichiers `documentation/live-final30-smoke-*-honest-summary-2026-08-26.txt` si ces preuves ont été publiées. Le script concerné est [`verify-live-final30-smoke.mjs`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/scripts/verify-live-final30-smoke.mjs).

## 10. Baselines visuelles

Le contrat visuel pointe explicitement vers [`tests/baselines-6.17.22`](https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1/tests/baselines-6.17.22), mais le manifest `SHA256SUMS` est absent. Le statut est donc **PENDING_BASELINE_CAPTURE / BLOCKED**.

Il faut capturer les 30 scénarios dans un environnement stable et déterministe, avec `PK_ALLOW_LOCAL_REBASELINE=1` uniquement lorsque la capture est explicitement autorisée. Ensuite, vérifier les images, générer le manifest SHA-256, publier les fichiers et faire effectuer une revue humaine indépendante. La CI ne doit jamais régénérer automatiquement les baselines et il ne faut pas modifier le design pour réduire les différences pixel.

## 11. Rollback, package et déploiement

L’upgrade v6.17.16 → v6.17.22 est PASS dans le runtime froid, mais le rollback n’est pas démontré. Exécuter le rollback uniquement dans un environnement disposable, avec une sentinelle de données, une vérification des réglages, une vérification des utilisateurs et une preuve d’idempotence. Ne pas tester un rollback destructif sur la production.

Le package WordPress doit être construit depuis le commit exact, avec le thème et le plugin séparés si nécessaire. Vérifier la présence de `theme/style.css` à la racine du ZIP du thème, `theme/functions.php`, les templates, les classes PHP, les assets et les lockfiles attendus. Comparer le SHA-256 de l’archive produite avec une seconde construction déterministe. Ne jamais inclure `vendor/` ou des caches de développement sans justification de packaging.

Après déploiement sur staging, vérifier le slug et le thème actif, les quatre plugins attendus, les versions, le body class final30, les 21 cartes FR/EN/AR et les routes REST. Ne pas déployer en production tant que le tableau de sortie ci-dessous n’est pas entièrement clôturé.

## 12. Commandes de validation locale

Avant tout push, exécuter les contrôles suivants dans un environnement propre :

```bash
bash scripts/check.sh
find partikulier-core theme -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/phpstan analyse partikulier-core theme/inc/class-search-filters.php --no-progress
vendor/bin/phpcs --standard=WordPress \
  --sniffs=WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification \
  partikulier-core/partikulier-core.php \
  partikulier-core/src/ListingRepository.php \
  partikulier-core/src/RateLimiter.php \
  theme/inc/class-search-filters.php \
  --report=summary
bash scripts/run-semgrep-v1.7.1.sh documentation/semgrep-v6.17.22.json
git diff --check
git diff --cached --check
```

Pour la preuve froide complète, utiliser [`ci-cold-acceptance-v1.7.1.sh`](https://github.com/hajarbenmlih91-cloud/partikulier2/blob/2ff8e387ac096c98283f1ff6eb5bc8e1ff859733/scripts/ci-cold-acceptance-v1.7.1.sh). Le run prend environ une heure, car il contient deux phases de 900 secondes. Conserver un seul run push officiel pour un candidat et annuler uniquement le doublon pull request.

## 13. Définition de terminé

| Condition de sortie | Statut de départ | Preuve exigée pour clôture |
|---|---|---|
| TTFB/LCP contractuels | **FAIL / BLOCKED infrastructure** | Mesures stables sous les seuils sur Hostinger réel |
| Burst 25 RPS | **FAIL** | Au moins 24,75 RPS effectifs, p95/p99 et CPU conformes |
| Saturation 50 RPS | **FAIL** | Probe conforme avec télémétrie CPU/RSS |
| Core et services | **PASS** | Nouveau run sur le SHA exact |
| UI 30 scénarios × 3 navigateurs | **24/30** | 30/30 et crawl localisé correct |
| Location | **BLOCKED** | Fixture staging traçable, filtre/badge/URL/JSON-LD prouvés puis nettoyage audité |
| Baselines 6.17.22 | **BLOCKED** | 30 images, manifest SHA-256, revue indépendante |
| Upgrade | **PASS technique** | Nouveau run reproductible et archive déterministe |
| Rollback | **NOT RUN** | Preuve disposable et sentinelle préservée |
| FR/EN/AR natif | **BLOCKED** | Attestations humaines réelles |
| WCAG/UX/design | **BLOCKED** | Revue indépendante réelle ; l’automatisation ne suffit pas |
| Commercial | **BLOCKED** | Approbation produit explicite |

Le verdict ne devient **GO** que lorsque chaque condition obligatoire est réellement PASS. L’accord personnel du propriétaire du projet peut être enregistré comme accord produit, mais ne remplace pas les validations indépendantes WCAG, UX, design, langues et commerciales.

## 14. Références principales

[1]: https://github.com/hajarbenmlih91-cloud/partikulier — Dépôt GitHub principal.

[2]: https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1 — Branche de reprise.

[3]: https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32972176681 — Run CI push officiel du candidat `2ff8e38`.

[4]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/release-approval-gate-v1.7.1/documentation/FINAL-RAPPORT-CLOTURE-CDC-V1.7.1-FINAL30-2026-08-26.md — Rapport historique NO-GO final30 à ne pas effacer.

[5]: https://blanchedalmond-reindeer-376379.hostingersite.com — WordPress staging autorisé, jamais la production.
