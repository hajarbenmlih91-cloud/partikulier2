# Objectifs strictement mesurables — 10 audits + 10 simulations

**Projet :** Partikulier
**Candidate de référence :** SHA final à renseigner par le run de clôture; le dernier SHA qualifié historiquement est `3f9c8e70e439f11c403894e92ae46f529b9ad986`, tandis que le contrat machine v1.1 est appliqué sur le prochain SHA dédié.
**Branche :** `automation/capacity-apcu-a58942c`
**Document source :** spécification de clôture O1–O22 jointe et contrat machine `documentation/objectives-contract-v1.0.json`. Les sections historiques AUDIT-01…SIM-10 restent archivées; l’addendum O1–O22 ci-dessous prévaut pour la décision finale.
**Règle générale :** aucun résultat non exécuté, partiel, local-only, non reproductible ou dépendant d’une autorisation absente ne peut être déclaré PASS.

## 1. Convention contractuelle commune

Chaque objectif doit produire un artefact horodaté contenant au minimum le SHA audité, la branche, l’environnement, la commande exacte, le code de sortie, la durée, les paramètres, le résultat brut et le résumé calculé. Les captures d’écran, JSON, logs et hashes doivent être conservés hors du dépôt de code puis attachés au dossier de preuves.

| Code de sortie | Signification obligatoire | Règle de décision |
|---:|---|---|
| `0` | Contrôle exécuté intégralement et cible respectée | Peut être **PASS**, uniquement si toutes les assertions de l’objectif sont vraies |
| `1` | Contrôle exécuté mais au moins une cible n’est pas respectée | **FAIL** bloquant |
| `2` | Contrôle invalide, incomplet, non exécuté ou incohérent | **INVALID/NOT RUN/INCONCLUSIVE/PARTIAL/MISSING**, jamais PASS |
| `75` | Dépendance externe indisponible malgré une demande documentée | **BLOCKED**, jamais PASS |
| `77` | Accès, autorisation, signoff ou décision produit manquante | **NOT AUTHORIZED**, jamais PASS |
| autre | Erreur d’outillage non classée | **FAIL** jusqu’à diagnostic et rejeu propre |

Une exécution interrompue par timeout, sortie tronquée, mauvais endpoint, mauvaise base, mauvais navigateur, mauvais SHA ou fixture absente est invalide. Le runner retourne `2`, `75` ou `77` selon la cause, joint la preuve et n’écrit jamais `PASS` dans le JSON.

## 2. Les 10 audits

### AUDIT-01 — Provenance, architecture et packaging

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `HEAD local = HEAD distant = SHA attendu` sur 40 caractères; `theme/style.css` et `theme/assets/css/style.css` présents; package ZIP attendu présent; `unzip -t` retourne `0`; deux reconstructions du package ont des SHA-256 identiques |
| **Preuve obligatoire** | `git rev-parse HEAD`, `git ls-remote`, `git ls-files`, listing ZIP, deux logs de build et `SHA256SUMS` |
| **Exit code attendu** | `0` |
| **PASS** | Toutes les égalités et les 5 conditions de présence/intégrité sont vraies |
| **FAIL** | Une égalité, un fichier, l’intégrité ZIP ou la reproductibilité échoue |
| **Environnement empêchant l’exécution** | Checkout non propre, SHA distant inaccessible, package manquant ou build non reproductible : `2 BLOCKED/INVALID`; aucune certification de packaging |

### AUDIT-02 — Syntaxe PHP

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `100 %` des fichiers `*.php` suivis dans `theme/`, `partikulier-core/` et `mu-plugins/` passent `php -l`; `0` erreur |
| **Preuve obligatoire** | Liste exhaustive des fichiers, sortie individuelle `php -l`, compteur `tested`, `passed`, `failed` |
| **Exit code attendu** | `0` |
| **PASS** | `failed = 0` et `tested > 0` |
| **FAIL** | Au moins une erreur de syntaxe ou fichier silencieusement exclu |
| **Environnement empêchant l’exécution** | PHP absent ou version non documentée : `2`; code source absent : `2` |

### AUDIT-03 — Syntaxe JavaScript

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `100 %` des fichiers JS/MJS inclus dans le périmètre de build passent `node --check`; `0` erreur |
| **Preuve obligatoire** | Manifest des fichiers testés, version Node, sortie de chaque `node --check`, compteurs |
| **Exit code attendu** | `0` |
| **PASS** | `failed = 0` et aucun fichier du manifest n’est manquant |
| **FAIL** | Une erreur de parsing, un fichier manquant ou un glissement de périmètre |
| **Environnement empêchant l’exécution** | Node absent ou manifest non déterministe : `2` |

### AUDIT-04 — Analyse statique sécurité

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | Semgrep sur le périmètre PHP du projet : `0 finding` bloquant, `0 erreur de scan`; règles, version et exclusions figées |
| **Preuve obligatoire** | JSON Semgrep complet, commande, version, nombre de règles, fichiers scannés et `errors` |
| **Exit code attendu** | `0` si findings bloquants = 0; `1` si findings bloquants > 0 |
| **PASS** | `results = 0`, `errors = 0`, aucune exclusion non documentée |
| **FAIL** | Un finding bloquant, une erreur, une exclusion globale ou une règle désactivée pour faire passer le contrôle |
| **Environnement empêchant l’exécution** | Semgrep/ruleset indisponible ou scan tronqué : `2`; le scanner heuristique PHP ne peut pas remplacer Semgrep |

### AUDIT-05 — Secrets dans le dépôt

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `0` secret opérationnel à haute confiance : clé privée, token API, mot de passe, cookie réel, dump SQL ou credential; placeholders explicitement allowlistés et non secrets |
| **Preuve obligatoire** | Scan du commit Git et de l’arbre suivi, manifest des motifs, résultats bruts, allowlist justifiée et revue des correspondances |
| **Exit code attendu** | `0` |
| **PASS** | `0` correspondance non allowlistée; les placeholders de test sont identifiés comme tels |
| **FAIL** | Au moins une correspondance non allowlistée ou un secret détecté |
| **Environnement empêchant l’exécution** | Scanner absent, commit non accessible ou historique non inspectable : `2`; ce n’est pas une autorisation de demander des secrets |

### AUDIT-06 — Surfaces REST/AJAX et headers publics

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | Matrice figée de `10` requêtes anonymes : chaque route publique attendue retourne son statut documenté; chaque route d’écriture protégée retourne `401` ou `403`; chaque AJAX sans nonce retourne `401` ou `403`; aucune donnée sensible n’est renvoyée |
| **Preuve obligatoire** | Requêtes/réponses HTTP complètes, méthode, URL, statut, headers de sécurité, corps normalisé et hash de l’artefact |
| **Exit code attendu** | `0` |
| **PASS** | `10/10` cas respectent leur statut attendu et aucun secret n’apparaît |
| **FAIL** | Un statut, header ou corps viole le contrat; route protégée absente (`404`) alors qu’elle est requise |
| **Environnement empêchant l’exécution** | Base URL indisponible, route attendue non déclarée dans le manifest ou réponse proxy ambiguë : `2`; ne pas requalifier `404` en protection |

### AUDIT-07 — Données Estatik et filtres

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | Fixture traçable `ID 249` + média `ID 250`; `a-vendre` retourne exactement `1` fixture; `a-louer` retourne `0` fixture; action inconnue retourne `0`; combinaison ville/type/action conserve `ville`, `image`, badge, prix et spécifications |
| **Preuve obligatoire** | JSON de fixture, HTML/JSON des requêtes, URL finale, compte de cartes, présence de l’ID/média/ville et journal de nettoyage |
| **Exit code attendu** | `0` |
| **PASS** | Toutes les assertions et les 3 exclusions/inclusions sont exactes |
| **FAIL** | Une action retourne le mauvais catalogue, ville/image/spec manque ou une fixture différente est utilisée |
| **Environnement empêchant l’exécution** | Fixture absente, ID modifié, accès Estatik indisponible ou seed non traçable : `2`; ne pas créer/modifier une nouvelle fixture sans autorisation |

### AUDIT-08 — Localisation arabe et JSON-LD

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `6/6` vues AR contrôlées; `lang` commence par `ar`; `dir = rtl`; au moins `1` texte visible arabe par vue; JSON-LD parse sans erreur; `0` résidu français interdit dans les champs ciblés |
| **Preuve obligatoire** | HTML, JSON-LD brut et parsé, liste des chaînes contrôlées, captures par vue et compteurs `passed/failed` |
| **Exit code attendu** | `0` |
| **PASS** | `6/6` vues satisfont toutes les assertions |
| **FAIL** | Un résidu, une vue, une direction, une langue ou un JSON-LD échoue |
| **Environnement empêchant l’exécution** | Route AR absente, données structurées absentes ou locale non chargée : `2`; ne pas extrapoler depuis le FR |

### AUDIT-09 — UI, UX, responsive et cross-browser

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `30/30` scénarios dans chacun des `3` navigateurs Chromium, Firefox et WebKit; `90/90` HTTP/locale/direction; image DOM `90/90`; crawl liens `90/90`; responsive `96/96` |
| **Preuve obligatoire** | JSON par navigateur, screenshots, manifest SHA256, rapports image DOM, crawl et responsive |
| **Exit code attendu** | `0` |
| **PASS** | Tous les compteurs ci-dessus sont exacts et aucun scénario n’est `ERROR` |
| **FAIL** | Une seule vue, image, URL, direction, largeur ou réponse échoue |
| **Environnement empêchant l’exécution** | Un navigateur, route ou dépendance manque : `2`; ne jamais remplacer WebKit/Firefox par Chromium seul |

### AUDIT-10 — Accessibilité automatisée

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `6/6` vues FR/EN/AR desktop/mobile; `0` violation axe; `0` violation `serious` ou `critical`; `0` bouton/input sans nom; `0` image sans `alt` |
| **Preuve obligatoire** | JSON axe complet, version axe/Playwright, viewport, violations et checks DOM par vue |
| **Exit code attendu** | `0` |
| **PASS** | Tous les compteurs sont à zéro et `passed = 6` |
| **FAIL** | Une violation ou un élément non nommé |
| **Environnement empêchant l’exécution** | Axe, navigateur ou route manquante : `2`; l’automatisation ne remplace pas une revue manuelle WCAG |

## 3. Les 10 simulations

Les simulations doivent être exécutées sur les navigateurs exigés par le contrat de campagne. La cible nominale est `3 navigateurs × 10 scénarios = 30 exécutions`; un environnement qui ne permet pas les trois navigateurs retourne `2`, pas PASS. Une simulation de formulaire ne doit jamais envoyer de données métier réelles : elle s’arrête après observation de l’URL, du DOM ou de la réponse.

### SIM-01 — Clic immédiat sur Affiner

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3` navigateurs; clic après `domcontentloaded` sans attente artificielle; panneau `aria-hidden=false`, classe `is-open`, bouton `aria-expanded=true` |
| **Preuve obligatoire** | Trace temporelle navigation/clic, navigateur, état DOM avant/après et screenshot |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` états cohérents |
| **FAIL** | Un navigateur reste fermé ou incohérent |
| **Environnement empêchant l’exécution** | Navigateur ou route absent : `2` |

### SIM-02 — Clic après chargement JavaScript

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3` navigateurs; `document.readyState=complete`; ressource `main.js` observée en HTTP 200; même état ouvert que SIM-01 |
| **Preuve obligatoire** | Performance entries, statut `main.js`, état ARIA/classes et log de clic |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` navigateurs passent les 4 assertions |
| **FAIL** | Course, double état ou script non chargé |
| **Environnement empêchant l’exécution** | `main.js` absent ou route indisponible : `2` |

### SIM-03 — Fermeture par bouton

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; après ouverture puis clic Fermer : `aria-hidden=true`, pas de classe `is-open`, backdrop fermé |
| **Preuve obligatoire** | États DOM avant/après, focus et trace du clic |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` fermetures correctes |
| **FAIL** | Un panneau reste ouvert ou le backdrop reste actif |
| **Environnement empêchant l’exécution** | Bouton ou panneau absent : `2` |

### SIM-04 — Fermeture par Échap et retour du focus

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; après Échap : panneau fermé et focus sur `.pk-filter-toggle` dans les `500 ms` |
| **Preuve obligatoire** | `document.activeElement`, timestamps et états ARIA/classes |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` respectent l’état et le délai |
| **FAIL** | Focus perdu, fermeture absente ou délai dépassé |
| **Environnement empêchant l’exécution** | Support clavier/navigateur non disponible : `2` |

### SIM-05 — Autocomplétion Ville du filtre

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; saisie `Marr`; réponse AJAX `200`; au moins `1` suggestion; sélection renseigne une valeur cachée non vide; aucune requête d’écriture |
| **Preuve obligatoire** | URL AJAX sans secret, statut, nombre de suggestions, valeur sélectionnée, trace réseau |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` remplissent les 5 assertions |
| **FAIL** | Suggestions absentes, valeur vide ou statut inattendu |
| **Environnement empêchant l’exécution** | Nonce de test non généré, AJAX absent ou seed sans villes : `2`; aucun nonce réel ne doit être demandé à l’utilisateur |

### SIM-06 — Autocomplétion de la recherche principale

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; saisie `Marr`; réponse `200`; au moins `1` suggestion; sélection renseignant `#pk-s-city-value` |
| **Preuve obligatoire** | Même journal réseau/DOM que SIM-05, avec sélecteurs du formulaire principal |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` |
| **FAIL** | Une assertion échoue |
| **Environnement empêchant l’exécution** | Champ ou AJAX absent : `2` |

### SIM-07 — Cumul Type + Ville

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; au moins `1` option type publiée; sélection d’un type et d’une ville; soumission sans écriture; HTTP final `200`; URL conserve simultanément `es_type` et `es_city` |
| **Preuve obligatoire** | Options avant action, valeur type, valeur ville, URL finale, statut HTTP et nombre de cartes |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` conservent les deux filtres et répondent 200 |
| **FAIL** | Un filtre est perdu, URL incorrecte ou réponse non 200 |
| **Environnement empêchant l’exécution** | Aucun terme type dans le seed, Polylang non initialisé ou route 404 : `2`, pas FAIL applicatif silencieux |

### SIM-08 — Reset après filtrage

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; après filtre puis clic reset, chemin exact `/fr/annonces/`; query string vide; HTTP final `200` |
| **Preuve obligatoire** | URL filtrée, URL reset, statut et état du formulaire |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` |
| **FAIL** | Paramètre conservé, mauvaise locale, mauvaise route ou HTTP non 200 |
| **Environnement empêchant l’exécution** | Lien reset absent ou route non provisionnée : `2` |

### SIM-09 — Rendu AR et données structurées

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; `lang=ar`, `dir=rtl`, au moins `1` titre/ville arabe visible; JSON-LD parse; `0` résidu français interdit |
| **Preuve obligatoire** | HTML, texte échantillonné, JSON-LD brut/parsé et assertions par navigateur |
| **Exit code attendu** | `0` |
| **PASS** | `3/3` |
| **FAIL** | Une vue conserve un résidu ou une direction incorrecte |
| **Environnement empêchant l’exécution** | Route AR ou JSON-LD absent : `2` |

### SIM-10 — Nonce, authentification et réflexion de script

| Champ | Contrat |
|---|---|
| **Cible chiffrée** | `3/3`; AJAX sans nonce retourne `401` ou `403`; écriture anonyme retourne `401` ou `403`; payload `<script>` n’est pas réfléchi; aucune donnée sensible dans la réponse |
| **Preuve obligatoire** | Requêtes anonymes, statuts, corps normalisés, headers et recherche exacte de la chaîne injectée |
| **Exit code attendu** | `0` |
| **PASS** | Les 4 assertions passent dans les 3 navigateurs |
| **FAIL** | Une écriture est acceptée, un nonce est ignoré ou une réflexion dangereuse apparaît |
| **Environnement empêchant l’exécution** | Endpoint absent alors qu’il est déclaré, base URL instable ou réponse intermédiaire : `2` |

## 4. Gates complémentaires obligatoires avant un GO release

Les 20 prompts ne suffisent pas à certifier un WordPress staging. Les gates suivants restent obligatoires et suivent exactement la même convention d’exit codes.

| Gate | Cible mesurable | Preuve obligatoire | PASS seulement si |
|---|---|---|---|
| SHA staging | `SHA réellement installé = 3f9c8e70...` | Preuve de déploiement et commande de vérification depuis le staging | Égalité exacte; sinon `2` si non vérifiable |
| TTFB | 20 mesures par route sur les 4 routes; p95 `< 800 ms` sur la métrique contractuelle | JSON DNS/TCP/TLS/wait/TTFB/total, headers, contexte et région | Les 4 p95 sont `< 800 ms`; sinon `1` |
| Capacity | 10 RPS, burst 25 RPS, write 2 RPS et 50 sessions selon le harnais inchangé | Rapport CI 4 CPU complet | Toutes les phases nominales respectent leurs seuils; sonde saturation séparément classée |
| Visual | 30 baselines canoniques, manifest SHA256 valide, `30/30` par navigateur | PNG, manifest, rapport pixel et revue humaine | Manifest valide, zéro mismatch bloquant et revue indépendante signée |
| Package | Deux rebuilds byte-identiques | ZIP, commandes, SHA256SUMS | Hash identique |
| Upgrade | Upgrade disposable sans erreur | Logs avant/après, version et smoke tests | `exit 0` et smoke tests verts |
| Rollback | Retour atomique à la version précédente | Journal sentinelle, chemins release et smoke tests | Version précédente restaurée, `exit 0`, idempotence prouvée |
| EN/AR | Décision écrite FR-only ou tests/traductions autorisés | Décision produit signée ou matrice EN/AR | Autorisation présente et critères passés; sinon `3` |
| Signoffs | UX, WCAG, RTL/langues, design, opérations et produit | Noms, rôles, date, SHA et signature | Tous présents; absence = `3` |

## 5. Règle de verdict

> **GO est interdit si un objectif ou un gate est `FAIL`, `BLOCKED`, `INVALID`, `NOT RUN` ou `NOT AUTHORIZED`.**

Un PASS local ne certifie pas le WordPress staging. Un PASS CI ne certifie pas un SHA différent déjà installé sur le staging. Une sortie vide, un routeur de test différent, un dataset incomplet, un navigateur remplacé, un timeout ou une capture non signée ne vaut pas PASS. Les tests ne doivent ni baisser leurs seuils, ni réduire leurs durées/RPS, ni désactiver une protection, ni créer une nouvelle fixture réelle sans autorisation.

## Références

[1]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/capacity-apcu-a58942c/theme/docs/reprise/07-AUDIT-10-PROMPTS-SIMULATIONS-2026-08-26.md "Document source des 10 audits et 10 simulations"
[2]: https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/capacity-apcu-a58942c "Branche candidate"
[3]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/capacity-apcu-a58942c/scripts/test-ui-v1.8.mjs "Harnais UI/responsive/crawl"
[4]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/capacity-apcu-a58942c/scripts/test-accessibility.mjs "Harnais axe/accessibilité"
[5]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/capacity-apcu-a58942c/scripts/visual.mjs "Contrat visuel et manifest"
[6]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/automation/capacity-apcu-a58942c/scripts/test-capacity-envelope-v1.7.1.py "Harnais capacity"
[7]: https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/ "WAI-ARIA Authoring Practices — Dialog Modal Pattern"


## Addendum contractuel v1.1 — O1 à O22

La spécification jointe « Mission de clôture — objectifs d’acceptation mesurables » est l’autorité de décision pour la version finale. Le contrat machine-readable `documentation/objectives-contract-v1.0.json` contient exactement 22 objectifs, O1 à O22, avec les codes `0` PASS, `1` FAIL, `2` INVALID/NOT RUN/INCONCLUSIVE/PARTIAL/MISSING, `75` BLOCKED et `77` NOT AUTHORIZED. Tout statut différent de PASS maintient automatiquement le NO-GO.

Les deux exigences supplémentaires sont obligatoires. **O21** impose de ne supprimer les fixtures staging 249 et 250 qu’après autorisation explicite de l’utilisateur, puis de fournir le journal de suppression et la vérification publique d’absence. Avant cette autorisation, O21 est `NOT_AUTHORIZED`, exit `77`, et aucune suppression automatique n’est permise. **O22** impose huit signoffs nommés et datés pour les langues FR/EN/AR, WCAG, UX, design, opérations et produit/commercial, tous rattachés au même SHA final. L’absence d’un responsable produit ou d’un relecteur est `NOT_AUTHORIZED`, exit `77`.

Le run CI `33132677584` sur `38135f9` reste un résultat historique du contrat précédent et ne constitue pas une certification O1–O22 : son pixel visual est `29/30`, son package est sauté et plusieurs objectifs externes ou humains restent ouverts. Les preuves de `3f9c8e70` ne peuvent pas certifier un SHA ultérieur.

Pour chaque objectif O1–O22, le prochain dossier doit indiquer le statut, l’exit code, le SHA, la branche, l’environnement, la commande, la cible, le résultat mesuré, la preuve horodatée, la date UTC, le blocage éventuel et l’action suivante. Aucun terme comme « appliqué », « vérifié », « terminé » ou « conforme » ne vaut preuve sans ces éléments.
