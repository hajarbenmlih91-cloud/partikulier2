# Passation senior — Partikulier CDC v1.8

**Document de reprise pour une nouvelle session.** Ce fichier décrit l’état réellement observé du dépôt `hajarbenmlih91-cloud/partikulier2`, de la PR #1 et de la candidate UI/UX v1.8. Il ne constitue pas un certificat de conformité.

> **Statut obligatoire au moment de la passation : `CANDIDATE — BLOCKED`.**
>
> Aucune conformité 100 %, aucune release finale, aucun tag protégé et aucune qualification « Ultra-Premium » ne doivent être déclarés tant que les gates techniques, les preuves de capacité, la décision visuelle sur les baselines, les validations humaines indépendantes et la comparaison de l’asset de release ne sont pas authentiquement verts.

## 1. Point de départ exact

| Élément | Valeur observée |
|---|---|
| Dépôt | [`hajarbenmlih91-cloud/partikulier2`](https://github.com/hajarbenmlih91-cloud/partikulier2) |
| PR | [#1 — release: require authentic human approvals](https://github.com/hajarbenmlih91-cloud/partikulier2/pull/1) |
| Branche candidate | `automation/release-approval-gate-v1.7.1` |
| Branche de base | `implementation/cdc-v1.7.1` |
| Head d’implémentation avant ce document | `151cc3e21d7301c846f27d859894872fde5eb561` |
| Dernier correctif source | `fix: preserve locale in property archive URLs` |
| État PR observé | ouverte, non draft, `REVIEW_REQUIRED` |
| Tag/release v6.17.17 | absents ; aucune publication autorisée |
| Version thème | 6.17.17 |
| Version de recette candidate | 6.17.17 / qualification de pipeline v1.7.1 |

Le SHA de merge utilisé par GitHub Actions sur une PR est différent du SHA de la branche. Les rapports cold peuvent donc porter `source_ref: refs/pull/1/merge` et un SHA synthétique. Il ne faut pas le confondre avec le SHA de branche. Toujours relever simultanément `headSha` du run, `source_commit` dans l’artefact et `git rev-parse HEAD` du checkout.

## 2. Dernier état CI réellement prouvé

Le dernier run candidate terminé au moment de cette passation est le [run `32787562048`](https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32787562048), déclenché par l’ancien commit de branche `6990143088885eb72e4d74799a07283538da4f23`. Il s’est terminé en **failure** le 24 août 2026 à 23:44 UTC. Le run ne valide donc pas le head source `151cc3e…`, qui doit être rejoué après la présente passation.

| Gate du run 32787562048 | Résultat observé |
|---|---:|
| Static contracts and dependency gates | SUCCESS |
| Cold WordPress/MariaDB/HTTP | FAILURE |
| Package déterministe | SKIPPED |
| Publication depuis tag approuvé | SKIPPED |
| Visual contract historique | 6/30 PASS, 24/30 FAIL, seuil 0,5 % |
| UI v1.8 scenarios | 0/30 PASS ; 30/30 contiennent un échec de crawl de liens |
| DOM images | 27/30 PASS |
| Responsive | 72/96 PASS |
| Qualification | `BLOCKED` |

L’artefact cold correspondant est `cold-acceptance-v1.7.1.tar.gz`, sous l’artefact GitHub `cdc-v1.7.1-cold-b94e551e014118b38640c11a5dda5e94c187ae5d`. Le rapport porte le merge SHA `b94e551e014118b38640c11a5dda5e94c187ae5d`, `run_id: 32787562048` et `source_ref: refs/pull/1/merge`.

### Capacité réelle mesurée dans ce run

La recette a cette fois atteint la matrice de capacité et a produit une métrique cgroup lisible. Cela constitue une preuve utile, mais le résultat reste **FAIL**.

| Phase | Résultat | Mesure importante |
|---|---:|---|
| `sustained_read_10rps` — 900 s | PASS | 9 000 réponses 200, 10,0 RPS, CPU moyen 22,985 %, p95 0,102 s, p99 0,107 s |
| `burst_read_25rps` — 60 s | FAIL | 1 500 réponses 200, 25,0 RPS, CPU moyen 85,154 % ; la limite CPU est dépassée |
| `write_api_2rps` — 900 s | PASS | 1 800 réponses 201, 2,0 RPS, CPU moyen 4,602 % |
| `concurrent_sessions_50` | PASS | 50 sessions, 0 erreur |
| `saturation_probe_50rps` — 10 s | FAIL | 30,2 RPS effectifs seulement, CPU moyen 92,309 %, p95 1,980 s |
| RSS cgroup | disponible | `cgroup-v2 memory.peak`, maximum 132 718 592 octets dans le rapport |
| Cgroup cible | disponible | `/sys/fs/cgroup/partikulier-reference-8090-5994` |
| Nettoyage | observé | 1 800 posts créés supprimés et 50 utilisateurs temporaires supprimés |

Le seuil de burst, le budget CPU et la saturation contrôlée ne sont pas verts. Il est interdit de conclure « 25 RPS conforme » uniquement parce que 1 500 réponses HTTP ont été 200 : le rapport indique explicitement `status: FAIL`.

## 3. Ce qui a été réalisé dans le dépôt

La branche candidate contient les contrats et la recette froide complète hérités des versions précédentes, puis les corrections v1.8 suivantes. Le détail exact de la divergence par rapport à la base est conservé dans `/home/ubuntu/v18-evidence/handoff-file-map.txt` côté sandbox ; le dépôt GitHub reste la source de code.

| Zone | Réalisation |
|---|---|
| CI | Workflow candidate avec `static → cold acceptance → package → release conditionnelle`; publication interdite sans tag et approbations authentiques |
| Provenance | Rapports restampillés avec SHA de 40 caractères, source ref et run ID ; l’auto-référence circulaire du SHA n’est pas utilisée |
| WordPress cold | WordPress 7.1, Estatik 4.3.4 verrouillé, Polylang 3.8.7, MariaDB, Nginx/PHP-FPM de référence |
| Core | Santé REST, règles métier, persistance, migrations, rate limiting, cache APCu et sync Estatik explicite |
| Visuel/UI | Correctifs cartes, médias, grille mobile, CTA, filtres, sticky safe-area, routes localisées et overrides Estatik |
| Images | Filtrage des médias invalides et validation locale `getimagesize`/taille/non-zéro avant rendu |
| i18n | FR/EN/AR, direction RTL arabe, fontes et liens de propriété localisés ; le dernier correctif reconstruit aussi l’archive CPT avec la langue courante Polylang |
| Authentification | POST anonyme refusé, nonce REST et écritures authentifiées couverts par les contrats existants |
| Capacité | Cgroup v2 dédié, rattachement Nginx/PHP-FPM, `memory.peak`, `memory.current`, `cpu.stat` et cible cgroup déclarée |
| Preuves UI v1.8 | Captures après, rapport DOM images, crawl liens et responsive 320/360/375/390 prévus et archivés dans la recette cold |
| Staging réel | Le sandbox Hostinger a été mis à niveau et contrôlé ; les six helpers temporaires ont été supprimés, sans toucher aux trois plugins fonctionnels |

## 4. Commits importants à préserver

| Commit | Sujet |
|---|---|
| `d3c9823f55b41ef94f5b3a04b220280f282a38f5` | Correctifs UI/mobile/images, harnais UI v1.8, cgroup et intégration CI |
| `8b9dc78012b5ae55c862d2b94aff0aa1b09262cc` | R6 et sélecteurs du design system |
| `357678f15fa42ef4028f9ac3ffe05d8820b5a591` | Utilisateur/groupe PHP-FPM |
| `fa0c461feee5361f909ddcdaa10eb60b7a04d13b` | Runtime cold sous `/tmp` |
| `3921756fa20f97b2fa4de91b050e4de2f953de2d` | Workers Nginx `www-data` |
| `ca4bbe923a74bf2fd65d66313ed4de02fbcea60b` | Diagnostic détaillé du premier échec cold |
| `d7ab4e5484912c41d987d6bc326aef60ae713f7b` | Barre CTA sticky limitée aux fiches immobilières |
| `fb4fc639dfbb9f43294071a5c6a8e35ba6fbc989` | Crawl de liens v1.8 rendu explicite pour ancres/auth/langues |
| `6990143088885eb72e4d74799a07283538da4f23` | Collecte des preuves cold avant le verdict final |
| `151cc3e21d7301c846f27d859894872fde5eb561` | Correction des URLs d’archive CPT avec langue Polylang |

L’historique de `v6.17.16` doit rester intact. Ne pas squasher, réécrire ou supprimer les commits historiques uniquement pour obtenir un SHA plus propre.

## 5. Carte des fichiers GitHub à examiner

### Contrats et gouvernance

| Fichier | Rôle |
|---|---|
| [`documentation/Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.1.md`](documentation/Cahierdeschargescontractuel—PartikulierUltra-Premiumv1.7.1.md) | CDC contractuel et règles de qualification |
| [`documentation/scope-matrix.csv`](documentation/scope-matrix.csv) | Périmètre M0/M1/M2 |
| [`documentation/visual-scenarios-v1.7.1.json`](documentation/visual-scenarios-v1.7.1.json) | 30 scénarios, langues, viewports, fixtures et politique baseline |
| [`tests/baselines-6.17.17/SHA256SUMS`](tests/baselines-6.17.17/SHA256SUMS) | Manifest immuable des baselines historiques |
| [`.github/workflows/cdc-v1.7.1-candidate.yml`](.github/workflows/cdc-v1.7.1-candidate.yml) | Pipeline candidate et condition de package/release |
| [`.github/workflows/cdc-v1.7.1-quality.yml`](.github/workflows/cdc-v1.7.1-quality.yml) | Gates qualité complémentaires |

### Recette froide et capacité

| Fichier | Rôle |
|---|---|
| [`scripts/ci-cold-acceptance-v1.7.1.sh`](scripts/ci-cold-acceptance-v1.7.1.sh) | Installation froide, suites, agrégation des exits et verdict final |
| [`scripts/start-reference-web.sh`](scripts/start-reference-web.sh) | Nginx/PHP-FPM 4 workers et cgroup v2 dédié |
| [`scripts/test-capacity-envelope-v1.7.1.py`](scripts/test-capacity-envelope-v1.7.1.py) | 10 RPS/900 s, 25 RPS/60 s, écritures 2 RPS/900 s, 50 sessions, saturation |
| [`scripts/load-test-http.sh`](scripts/load-test-http.sh) | Charge et métriques p95/p99 préalables |
| [`scripts/test-upgrade-v1.7.1.sh`](scripts/test-upgrade-v1.7.1.sh) | Upgrade v6.17.16 → v6.17.17 et idempotence |
| [`scripts/measure-sql-senior.php`](scripts/measure-sql-senior.php) | Mesures SQL froides |
| [`scripts/test-hmac-http.sh`](scripts/test-hmac-http.sh) | HMAC HTTP, cinq rounds et rejets 401 |

### UI, i18n et thème

| Fichier | Rôle |
|---|---|
| [`scripts/visual-contract-v1.7.1.mjs`](scripts/visual-contract-v1.7.1.mjs) | Comparaison stricte 30 scénarios, seuil 0,5 %, sans régénération CI |
| [`scripts/test-ui-v1.8.mjs`](scripts/test-ui-v1.8.mjs) | Captures, DOM images, crawl de liens et responsive 320/360/375/390 |
| [`scripts/test-i18n-fonts.mjs`](scripts/test-i18n-fonts.mjs) | Langues, fontes, `lang` et `dir` |
| [`scripts/test-i18n-journey.mjs`](scripts/test-i18n-journey.mjs) | Parcours i18n |
| [`theme/functions.php`](theme/functions.php) | Résolution des URLs d’archive et intégration thème |
| [`theme/inc/class-avif.php`](theme/inc/class-avif.php) | Validation des médias AVIF et URLs d’images |
| [`theme/inc/class-listing-urls.php`](theme/inc/class-listing-urls.php) | URLs localisées des propriétés |
| [`theme/templates/header.php`](theme/templates/header.php) | Header, langues et CTA mobile de fiche |
| [`theme/templates/archive.php`](theme/templates/archive.php) | Archive, grille, filtres et compteur |
| [`theme/templates/parts/card-property.php`](theme/templates/parts/card-property.php) | Carte canonique, image et lien propriété |
| [`theme/estatik4/front/property/content-archive.php`](theme/estatik4/front/property/content-archive.php) | Override carte Estatik/archive |
| [`theme/estatik4/front/property/single.php`](theme/estatik4/front/property/single.php) | Override fiche Estatik, galerie et contact |
| [`theme/assets/css/style.css`](theme/assets/css/style.css) | Couche responsive et design system finale |
| [`theme/assets/js/main.js`](theme/assets/js/main.js) | Menu, filtres, sticky clavier et favoris |
| [`theme/assets/js/submit-steps.js`](theme/assets/js/submit-steps.js) | Dépôt en trois étapes, validation et libellés à localiser |

## 6. Ce qui reste à faire — ordre senior obligatoire

### A. Rejouer la candidate sur le nouveau head

Le run `32787562048` ne couvre pas `151cc3e…`. Après le commit de ce document, relever le nouveau `HEAD`, vérifier que la branche est propre et attendre un nouveau run candidate. Ne jamais présenter le run 699 comme une validation du nouveau head.

Commandes de départ :

```bash
git fetch origin
git status --short --branch
git rev-parse HEAD
gh pr view 1 --repo hajarbenmlih91-cloud/partikulier2 --json headRefOid,reviewDecision,state
gh run list --repo hajarbenmlih91-cloud/partikulier2 --workflow cdc-v1.7.1-candidate.yml --limit 10
```

### B. Corriger puis rerun le crawl UI v1.8

Le rapport `documentation/ui-v1.8/ui-summary.json` de l’artefact du run 699 indique `image_dom_passed: 27/30`, `link_crawl_passed: 0/30` et `responsive_passed: 72/96`. Le rapport de liens montre notamment des liens `/annonces/` en 404 et des liens d’accueil non préfixés par la langue. Le correctif `151cc3e` traite la résolution de l’archive CPT via `pll_home_url`; il faut confirmer son effet dans un cold neuf.

Le nouveau harnais distingue maintenant les ancres de même page, endpoints WordPress d’authentification, endpoints `/wp-*` et sélecteurs de langue. Il ne faut pas réautoriser tous les liens en bloc. Tout lien de contenu doit encore aboutir à HTTP 200 et rester dans la langue attendue après redirections. Les trois lignes `image-dom-invalid` doivent être inspectées dans `image-dom-report.json`; un rapport PASS ne peut pas être obtenu en supprimant la vérification `complete`, dimensions naturelles, ressource vue et statut HTTP.

### C. Arbitrer la contradiction baseline/design de manière traçable

La baseline `single-fr-desktop` versionnée contient des images cassées et l’alt text de `Maison témoin Casablanca`; la candidate affiche des images décodées. Le cold mesure environ 15 % de différence sur les fiches, et les homes mobiles/archives/dépôts mobiles échouent aussi à cause des changements v1.8. Il s’agit d’une divergence produit importante, pas d’une permission de remettre les images cassées.

La politique actuelle est explicite : `regenerate_in_ci=false`, seuil 0,5 %, approbation nécessaire pour rebaseline. Deux seules voies sont acceptables : soit corriger le code pour reproduire réellement la baseline approuvée sans réintroduire un défaut P0, soit obtenir une décision indépendante et créer un jeu v1.8 séparé en conservant les anciennes captures, leur `SHA256SUMS`, les diffs, la raison, la date et l’approbation. Ne jamais écraser `tests/baselines-6.17.17` silencieusement et ne jamais élargir le seuil pour faire passer le run.

### D. Résoudre la capacité sans déplacer les poteaux

La référence reste **4 workers PHP-FPM** et l’enveloppe reste exactement celle du CDC. Le burst a obtenu 25 RPS mais a dépassé 80 % CPU ; la saturation n’a atteint que 30,2 RPS effectifs. Il faut profiler le vrai coût — requêtes, Estatik, PHP-FPM, Nginx, connexions ou scheduling — puis corriger l’architecture ou l’overhead. Il est interdit de passer à plus de workers, de baisser le seuil CPU, de raccourcir les phases ou de déclarer PASS sur la seule absence d’erreurs HTTP.

Le prochain rapport doit continuer à inclure `cgroup_target`, `cgroup_path`, `cgroup_memory_source`, `memory.peak`, `memory.current`, `cpu.stat` et un snapshot de `cgroup.procs` permettant de vérifier que les enfants PHP-FPM et Nginx sont bien dans le cgroup déclaré. La preuve RSS du run 699 est disponible mais le gate capacité demeure FAIL.

### E. Maintenir le staging réel séparé de la CI froide

Le staging autorisé est [`blanchedalmond-reindeer-376379.hostingersite.com`](https://blanchedalmond-reindeer-376379.hostingersite.com). Il contient WordPress 7.1, Estatik 4.3.4, Polylang 3.8.7, Partikulier Core 1.0.0 et le thème candidat construit depuis l’état d3. Il n’a plus que les trois extensions fonctionnelles actives ; les six helpers temporaires ont été supprimés.

Ce staging sert à contrôler les parcours, les langues, l’authentification, les médias et l’UX réelle. Il ne prouve pas le SQL, le HMAC, le cgroup ou la capacité du SHA Git en CI. Après export et conservation des preuves, inspecter puis supprimer uniquement les fixtures QA confirmées : Core IDs 21/22/23, propriétés Estatik 155/157/159 et brouillons automatiques 153/154/156/158 si leur nature est prouvée. Ne pas supprimer de contenu original. Conserver une preuve avant/après et confirmer dans l’interface ou REST authentifié.

### F. Obtenir les validations humaines réelles

Les validations humaines ne peuvent pas être fabriquées par une recette automatisée. Il faut demander, sur le commit exact qui a passé les gates techniques, des décisions distinctes et identifiables pour la revue technique, UI/UX/WCAG, contenu natif FR/EN/AR, visuel/design system et décision commerciale M2. Chaque sign-off doit mentionner le reviewer, la date, le commit, le périmètre et `PASS` ou `FAIL`.

Tant que ces validations ne sont pas attachées à la PR, les champs `PENDING_NOT_SIMULATED` restent tels quels. La PR est actuellement `REVIEW_REQUIRED`; ne pas utiliser l’identité du bot comme reviewer indépendant et ne pas créer une approbation à la place d’une personne.

### G. Publier seulement après clôture complète

Une fois seulement que les gates techniques, visuels approuvés, capacité, upgrade, preuves et sign-offs indépendants sont verts, le propriétaire du dépôt peut créer le tag protégé. Le workflow de tag doit construire le package depuis ce tag immuable. Ensuite, télécharger l’asset publié et prouver :

```bash
sha256sum -c SHA256SUMS
cmp --silent package-from-tag.zip downloaded-release-asset.zip
printf 'CMP_EXIT=%s\n' "$?"
```

L’attestation finale doit relier le tag, le commit de 40 caractères, le SHA du ZIP construit, le SHA de l’asset téléchargé, le résultat `sha256sum -c`, le résultat `cmp`, le run de publication et les approbations humaines. Avant cette étape, `FINAL_RELEASE` reste `PENDING_NOT_PUBLISHED`.

## 7. Preuves locales conservées dans le sandbox

Ces fichiers ont été conservés hors dépôt pour éviter de polluer les baselines et le code. Une nouvelle session peut ne pas retrouver le même workspace ; les runs GitHub et leurs artefacts restent les références principales.

| Emplacement | Contenu |
|---|---|
| `/home/ubuntu/v18-evidence/before-2d99c18/` | Captures/diffs et `SHA256SUMS` avant corrections |
| `/home/ubuntu/v18-evidence/d7ab4e5-visual-comparison.json` | Tableau hashes/diffs du cold d7ab4e5 |
| `/home/ubuntu/v18-evidence/d7ab4e5-visual-comparison.md` | Lecture humaine des 30 scénarios |
| `/home/ubuntu/v18-evidence/d7ab4e5-mobile-offsets.txt` | Analyse offsets mobiles |
| `/home/ubuntu/ci-artifacts-ca4bbe9/` | Artefact cold ca4bbe9, 6/30 visual PASS |
| `/home/ubuntu/ci-artifacts-d7ab4e5/` | Artefact cold d7ab4e5, 6/30 visual PASS |
| `/home/ubuntu/ci-artifacts-fb4fc63/` | Artefact cold fb4fc63, 6/30 visual PASS |
| `/home/ubuntu/ci-artifacts-6990143/` | Artefact cold final observé ; le rapport UI et la capacité y sont produits |
| `/home/ubuntu/ci-evidence-ca4bbe9-failed.log` | Log brut du premier diagnostic visuel |
| `/home/ubuntu/staging-qa-findings-v1.7.1.md` | Journal QA staging, historique et nettoyage |
| `/home/ubuntu/v1.8-user-requirements.md` | Exigences utilisateur v1.8 durablement résumées |

Les hashes connus du dossier de comparaison d7 sont :

```text
0bdc18581fea37860b1bae241f42bc480b9b940eb06e5728b6b46dfe8ed44fca  d7ab4e5-visual-comparison.json
0b2aac71a61b78070801a4693200f9152e71d75f587089e705826811371c26b7  d7ab4e5-visual-comparison.md
b02d64645b7ebd2225c6670c16eb03b1ec90258fe6dd3047f690ed59aa966ba1  d7ab4e5-mobile-offsets.txt
```

## 8. Checklist de reprise courte

| Ordre | Condition de sortie |
|---:|---|
| 1 | Checkout propre, branche et SHA exacts relevés |
| 2 | Nouveau run candidate exécuté sur le head après `151cc3e…` et après le commit de passation |
| 3 | UI v1.8 : 30/30 scénarios, 30/30 DOM images, 30/30 crawl de contenu et 96/96 responsive |
| 4 | Visual : décision baseline indépendante documentée ; aucun seuil assoupli ni baseline historique écrasée |
| 5 | Capacité : toutes phases PASS, 25 RPS réel, CPU/RSS/cgroup lisibles, saturation contrôlée/récupération PASS |
| 6 | Upgrade, SQL, HMAC, routes, E2E, accessibility, fonts, sorting et Semgrep PASS avec rapports/hash |
| 7 | Nettoyage staging ciblé prouvé sans suppression de contenu original |
| 8 | Sign-offs humains indépendants FR/EN/AR, technique, UI/WCAG, contenu et commercial réellement attachés |
| 9 | Tag protégé, package de tag, asset publié, `sha256sum -c` et `cmp` PASS |
| 10 | Seulement alors, réévaluer `ULTRA_PREMIUM`, `FINAL_RELEASE` et le verdict global |

## 9. Règles de sécurité et d’honnêteté

Ne pas demander, écrire ou publier de mot de passe. Ne pas utiliser le staging comme preuve de la CI froide. Ne pas supprimer de baselines ou d’historique v6.17.16. Ne pas transformer un modèle d’approbation en signature. Ne pas déclarer une conformité totale à partir d’un run local, d’un run ancien, d’un artifact téléchargé sans comparaison ou d’une métrique HTTP partielle.

> La bonne prochaine action est une **nouvelle recette froide sur le head exact**, suivie du diagnostic des rapports UI/capacité. Le bon statut de départ reste **CANDIDATE — BLOCKED**.

## Références

[1]: https://github.com/hajarbenmlih91-cloud/partikulier2 "Dépôt GitHub Partikulier"
[2]: https://github.com/hajarbenmlih91-cloud/partikulier2/pull/1 "PR #1 — release: require authentic human approvals"
[3]: https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32787562048 "Run candidate 32787562048"
[4]: https://blanchedalmond-reindeer-376379.hostingersite.com "Staging WordPress explicitement autorisé"
