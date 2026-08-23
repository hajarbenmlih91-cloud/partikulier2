# Cahier des charges contractuel — Partikulier Ultra-Premium v1.7

**Statut :** version amendée et exécutable.
**Version précédente :** v1.6, conservée comme historique non réécrit.
**Objet :** développement, recette, packaging, release et sign-off de Partikulier.

> Cette version remplace v1.6 pour toute nouvelle candidate. Elle ne rend pas rétroactivement conformes les releases antérieures. Une version publiée reste immuable et doit être évaluée avec le CDC qui lui était applicable, sauf décision contractuelle écrite contraire.

## 1. Principes contractuels

Partikulier est une plateforme immobilière trilingue composée d’un thème, d’un cœur métier, de scripts de recette et d’un dispositif de preuves. Le mot **Ultra-Premium** désigne un résultat qui satisfait simultanément les gates techniques, fonctionnelles, UX, linguistiques, opérationnelles et commerciales définies ici. Il ne peut pas être déduit du seul passage de l’E2E, du lint ou d’un test pixel.

Le fournisseur ne peut déclarer une fonctionnalité, une métrique ou une certification que si elle est reliée à un fichier, une commande, un code retour, une sortie brute, un environnement et un commit vérifiables. Une absence, une hypothèse ou une exécution non réalisée doit être déclarée comme telle.

| Niveau | Signification | Règle de livraison |
|---|---|---|
| M0 | Socle obligatoire | Toute installation et toute recette doivent le fournir et le tester. |
| M1 | Expérience Ultra-Premium | Obligatoire pour le label Ultra-Premium. |
| M2 | Option commerciale | Peut être séparé, mais doit être déclaré `NOT_IMPLEMENTED` ou `NOT_COVERED` s’il est absent. |

Une capacité non livrée ne peut jamais être convertie en `PASS` par une phrase, une capture ou une intention future. Toute modification du périmètre exige un diff, une justification, un impact, une estimation et une approbation écrite.

## 2. Séparation des statuts et des labels

Les statuts d’implémentation, de test et de décision sont indépendants.

**Statuts d’implémentation :** `IMPLEMENTED`, `NOT_IMPLEMENTED`, `NOT_COVERED`, `DEPRECATED`.

**Statuts de test :** `PASS`, `FAIL`, `NOT_RUN`, `SKIPPED`, `NO_BASELINE`, `NON_REPRODUCIBLE`, `BLOCKED`.

**Statuts de décision :** `PASS`, `FAIL`, `PENDING`, `NOT_APPLICABLE`.

Le statut `PARTIAL` est interdit dans les preuves de test et dans le verdict final. Pour une capacité incomplète, le document doit utiliser séparément un statut d’implémentation et un statut de test. `NOT_APPLICABLE` est réservé à une exigence réellement hors périmètre approuvée dans `scope-matrix.csv`; il ne peut pas masquer un test obligatoire non exécuté.

Les labels suivants sont séparés :

| Label | Condition |
|---|---|
| `TECHNICAL_RELEASE` | Toutes les gates techniques M0 applicables sont `PASS`, le package et la provenance sont cohérents. |
| `UX_CONTENT` | Revue UI/UX indépendante, accessibilité et attestations linguistiques positives. |
| `COMMERCIAL_RELEASE` | Périmètre vendu, paiement, support et promesses commerciales approuvés par le responsable produit/client. |
| `ULTRA_PREMIUM` | Les trois labels précédents sont positifs, la charge M1 est `PASS`, et aucune réserve bloquante ne subsiste. |

`LABEL_AUTORISÉ = OUI` est réservé à `ULTRA_PREMIUM = PASS`. Une release techniquement exploitable mais non approuvée humainement doit utiliser la formulation **RELEASE CANDIDATE** ou **CONFORME TECHNIQUEMENT SOUS RÉSERVES**.

## 3. Périmètre et enveloppe d’exploitation

Avant le développement ou toute modification majeure, le fournisseur publie et fait approuver :

```text
documentation/scope-matrix.csv
documentation/capacity-envelope.json
documentation/compatibility-matrix.json
documentation/implementation-deviations.md
documentation/technical-design.md
documentation/data-contract.json
documentation/routes-contract.json
```

L’enveloppe de référence v1 est la suivante :

| Ressource | Objectif |
|---|---:|
| Annonces publiées | 10 000 |
| Utilisateurs enregistrés | 1 000 |
| Sessions simultanées | 50 |
| Lecture publique soutenue | 10 req/s pendant 15 min |
| Lecture publique en rafale | 25 req/s pendant 60 s |
| Écriture/API | 2 req/s pendant 15 min |
| Workers PHP | 4 |
| Mémoire de recette de charge | 2 Go minimum |

L’environnement de charge doit aussi préciser OS, CPU, mémoire, stockage, réseau, PHP-FPM, serveur HTTP, MariaDB, cache, nombre de workers, fixture, tailles d’images et distribution des parcours. Une mesure sur un runner partagé ne constitue pas une garantie de production.

Les seuils de référence sont p95 ≤ 1,5 s, p99 ≤ 3 s, erreurs ≤ 0,1 %, CPU moyen ≤ 80 %, mémoire ≤ 2 Go et maintien du débit soutenu pendant 15 minutes. Toute valeur manquante est `FAIL` ou `NOT_RUN`, jamais `PASS`. Le mot `scalable` est interdit sans enveloppe chiffrée et mesure correspondante.

## 4. Provenance, preuves et reproductibilité

### 4.1 Deux classes de fichiers

La provenance est séparée en deux classes afin d’éviter les preuves auto-référentielles.

| Classe | Contenu |
|---|---|
| Preuves versionnées dans Git | Code, contrats, schémas, règles, scripts, baselines approuvées, matrices, politique de masquage et modèles de rapports. |
| Preuves générées par CI | Résultats, environnement réellement installé, commandes, codes retour, métriques, run ID, commit du checkout, hashes et artefacts. |

Un fichier versionné dans Git ne doit pas prétendre contenir le SHA exact du commit qui le contient. Un commit ne peut pas être auto-référentiel de cette manière : l’ajout du SHA modifie le contenu et donc le SHA. Le dépôt versionne donc `documentation/environment-spec.json` et les schémas ; la CI produit `documentation/evidence/environment.json` après checkout.

### 4.2 Métadonnées obligatoires des preuves CI

Toute preuve générée doit contenir au minimum :

```json
{
  "test_id": "IDENTIFIANT_STABLE",
  "candidate_version": "6.17.x",
  "source_commit": "40 caractères hexadécimaux du checkout",
  "source_ref": "tag ou ref de CI",
  "run_id": "identifiant GitHub ou identifiant local explicite",
  "started_at_utc": "date ISO-8601",
  "finished_at_utc": "date ISO-8601",
  "command": "commande exacte",
  "environment_file": "chemin vers le manifeste généré",
  "fixture": "identifiant et taille de fixture",
  "status": "PASS|FAIL|NOT_RUN|SKIPPED|NO_BASELINE|NON_REPRODUCIBLE|BLOCKED",
  "exit_code": 0,
  "artifacts": ["chemins relatifs"],
  "limitations": []
}
```

Les valeurs `uncommitted`, `${COMMIT}`, `<40 hex>`, `TBD`, `TODO` et `N/A` sont interdites dans une preuve présentée comme finale. Les scripts doivent refuser de produire une preuve finale si le commit, la version ou la commande est absent.

### 4.3 Niveaux de reproductibilité

Les rapports doivent déclarer explicitement le niveau démontré :

| Niveau | Signification |
|---|---|
| `CI_EVIDENCE_REPRODUCIBLE` | Les preuves sont générées par le checkout CI identifié et rattachées à son run. |
| `PACKAGE_REPRODUCIBLE` | Deux builds avec les mêmes entrées, dont branche et checkout détaché, sont byte-identiques. |
| `SOURCE_TO_RELEASE_REPRODUCIBLE` | Un build autonome depuis le tag, avec les entrées documentées, est byte-identique à l’asset release. |
| `NON_REPRODUCIBLE` | Une entrée, un outil, un artefact ou une comparaison obligatoire manque. |

`PACKAGE_REPRODUCIBLE` ne doit pas être présenté comme `SOURCE_TO_RELEASE_REPRODUCIBLE`. Le package doit produire un manifeste d’entrées contenant au minimum le commit, les versions d’outils, les archives vendor, les baselines et les hashes des preuves. Le job release doit vérifier le tag, construire une fois, calculer le SHA, publier le même fichier, télécharger l’asset et exécuter `cmp`.

## 5. Architecture obligatoire

La livraison cible comprend :

```text
theme/
partikulier-core/
partikulier-pro/
scripts/
tests/
documentation/
.github/workflows/
```

Le thème reste capable d’afficher une expérience de base lorsque `partikulier-core` est absent. Il doit désactiver proprement les fonctions dépendantes avec un avis administrateur et ne jamais provoquer de fatal error.

`theme/functions.php` est un bootstrap court. Le paiement, les webhooks, les migrations, les permissions métier, la modération, les favoris, les alertes, les leads, les jobs et les traitements lourds appartiennent au core ou au module approprié.

Le core doit documenter et tester, lorsque ces capacités sont dans M0/M1 : `ListingRepository`, `ListingService`, `ListingPolicy`, `ListingMediaService`, `SearchService`, `TranslationService`, `ModerationService`, `LeadService`, `FavoriteService`, `SavedSearchService`, `JobRunner`, `AuditLogger`, `PrivacyService` et `HealthCheck`. Toute méthode publique doit avoir un contrat de types, exceptions, capacités, effets persistants et au moins un test ciblé, ou être marquée `NOT_COVERED`.

Les tables utilisent `$wpdb->prefix`, les dates UTC et des migrations versionnées. Les requêtes sont préparées. Le migrateur est idempotent, interrompable sans corruption, visible dans l’écran de santé et testé sur base vide ainsi que depuis la version précédente. Un rollback de migration et une procédure de restauration doivent être testés.

## 6. Contrats REST et données

Toute route stable doit déclarer méthode, chemin canonique, version, authentification, permission callback, capacité, paramètres, schéma d’entrée, schéma de sortie, erreurs, codes HTTP, rate limit, cache, redirections autorisées et tests associés. Une route absente du contrat n’est pas stable.

Le minimum fonctionnel est :

```text
GET  /wp-json/partikulier/v1/listings
GET  /wp-json/partikulier/v1/listings/{id}
POST /wp-json/partikulier/v1/listings
POST /wp-json/partikulier/v1/leads
POST /wp-json/partikulier/v1/favorites
POST /wp-json/partikulier/v1/automation-event
GET  /wp-json/partikulier/v1/health
```

Les paramètres de tri sont appliqués côté serveur par liste blanche. Les tests vérifient l’ordre réel des résultats. Une redirection qui masque un 404 est un échec. Les réponses publiques ne contiennent ni secret, ni nonce d’un autre contexte, ni détail d’infrastructure inutile. Les JSON-LD sont assainis, encodés dans leur contexte et testés avec guillemets, balises, antislashes, Unicode, URLs et valeurs utilisateur.

`X-Forwarded-For` n’est fiable que derrière un proxy explicitement déclaré. Les tests couvrent proxy fiable, proxy non fiable, en-tête forgé et chaîne multi-proxy.

## 7. Preuves obligatoires et sécurité

Chaque suite doit écrire un JSON généré par la commande, un log brut et un code retour. Un fichier `results: []` écrit manuellement est refusé. Le rapport global est généré depuis les JSON par un script qui refuse tout texte de certification si une gate obligatoire n’est pas `PASS`.

Semgrep doit analyser les octets réels de la portée déclarée avec des règles identifiées. La preuve contient version, commande exacte, commit, run, règles, chemins réellement analysés, nombre de cibles, taille en octets, erreurs et findings. Les compteurs doivent être dérivés de la sortie brute du moteur et non d’un inventaire indépendant.

Le HMAC de production utilise le secret décodé en octets, `hash_equals()`, un timestamp contrôlé, une clé active et, si prévu, une clé précédente dans une fenêtre documentée. Le canonical string est :

```text
METHOD + "\n" + PATH_WITHOUT_WP_JSON_PREFIX + "\n" + TIMESTAMP + "\n" + RAW_BODY
```

Les tests couvrent signature valide, signature invalide, secret incorrect, timestamp expiré, event ID dupliqué, clé active, clé précédente, corps modifié et cinq rounds avec deux processus HTTP indépendants. La trace affiche codes HTTP, round, event ID haché, body haché et idempotence, sans secret ni donnée sensible en clair.

La CI ajoute un scan des secrets, un audit de dépendances PHP/Node, un SBOM, les versions et hashes des artefacts vendor, ainsi que la vérification des catalogues `.po`, `.mo` et `.pot`. HMAC, Semgrep et une table d’audit sont des contrôles techniques ; ils ne constituent pas une conformité réglementaire.

## 8. Internationalisation, accessibilité et UX

Les langues supportées sont FR, EN et AR. Le périmètre exact obligatoire est déclaré dans `scope-matrix.csv`. Par défaut, il comprend accueil, navigation, recherche, listes, fiche annonce, pagination, dépôt, favoris, mes annonces, authentification publique, erreurs, succès, emails transactionnels, SEO, JSON-LD et taxonomies visibles.

La recette vérifie familles de traductions, URLs, titres, descriptions, métadonnées, hreflang `fr`, `en`, `ar`, `x-default`, `dir="rtl"`, police réellement chargée, ordre logique, messages et fallback documenté. Une attestation native séparée doit préciser rôle, absence de conflit, langue, périmètre, commit du build relu, date UTC, méthode, écrans, réserves, décision et signature.

Les formulaires valident côté client et serveur. Le clavier atteint tous les contrôles avec focus visible. Les contrastes ciblent WCAG 2.2 AA pour texte, contrôles et focus [1]. Les tests couvrent vide, format invalide, longueur, upload refusé/accepté, réseau lent, réponses 4xx/5xx, clavier, lecteur d’écran ou protocole équivalent, RTL, mobile 375×667 et desktop 1280×800.

Le test visuel couvre 30 scénarios, soit 5 pages/parcours × 3 langues × 2 formats. Il est `PASS` seulement avec 30/30, zéro erreur, zéro absence de baseline, diff maximal ≤ 0,5 % et RTL réussi. Les baselines appartiennent au même commit et à la même fixture. La CI ne régénère jamais une baseline après échec. Toute rebaseline exige ancienne baseline, nouvelle baseline, hashes, captures avant/après, résultat précédent, raison et approbateur indépendant.

La revue UI/UX exige au moins trois reviewers, au maximum un membre de l’équipe de développement, avec conflit d’intérêts déclaré. La grille 1–5 couvre hiérarchie, cohérence, typographie, images, densité, responsive, RTL, micro-interactions, accessibilité et confiance. La moyenne globale doit être ≥ 4,2/5, chaque reviewer ≥ 3,8/5 et chaque dimension ≥ 3,5/5. La revue pixel ne remplace jamais cette revue humaine.

## 9. Performance et charge

La fixture fonctionnelle comprend 30 annonces publiées en FR/EN/AR. La fixture de charge comprend 1 000 annonces, des photos sur au moins 30 % des annonces et 1 000 utilisateurs si la capacité est dans le périmètre approuvé.

Toute mesure publie fixture, cache froid/chaud, itérations, machine, versions, plugins, workers, requêtes, durée, mémoire, CPU, p50, p95, p99, minimum, maximum, débit, taille des réponses et erreurs. Les budgets SQL 30 annonces et les budgets p95/p99 1 000 annonces sont des séries distinctes. Une optimisation qui retire du contenu ou contourne le rendu est un échec.

Le protocole HTTP comporte au moins 30 requêtes d’échauffement et 100 requêtes mesurées, séparées en cache froid et chaud. La charge 1 000 annonces doit être `PASS` pour `ULTRA_PREMIUM`. `NOT_RUN` est autorisé pour une release candidate explicitement nommée **Premium non certifié charge**, mais interdit au label Ultra-Premium.

## 10. Sauvegarde, restauration, rollback et observabilité

Avant le label Ultra-Premium, le fournisseur définit et teste :

| Élément | Exigence minimale |
|---|---|
| RPO | Valeur chiffrée approuvée, fréquence et périmètre des sauvegardes. |
| RTO | Valeur chiffrée approuvée et durée mesurée de restauration. |
| Sauvegardes | Chiffrement, rétention, accès, intégrité et emplacement documentés. |
| Restauration | Test sur environnement séparé avec données et réglages vérifiés. |
| Rollback | Code, migration, configuration et procédure de retour testés. |
| Observabilité | Logs corrélés, erreurs, métriques, alertes et incident simulé. |

Un backup non restauré avec vérification fonctionnelle ne constitue pas une preuve de sauvegarde exploitable.

## 11. CI/CD, protections et release

Les workflows minimaux sont :

```text
.github/workflows/quality.yml
.github/workflows/wordpress-matrix.yml
.github/workflows/load-test.yml
.github/workflows/release.yml
```

`quality.yml` valide lint PHP/JS/CSS/JSON, schémas, statuts, placeholders, versions, secrets, dépendances, SBOM, Semgrep et preuves visuelles. `wordpress-matrix.yml` sépare WordPress stable, beta et alpha ; seules les versions stables contribuent au verdict stable. Toute preuve d’environnement archive `php -v`, `command -v php`, `php --ini`, `php -m`, WordPress, plugins, binaire utilisé et logs.

`release.yml` doit :

1. vérifier le tag et son commit cible ;
2. vérifier que les preuves CI portent ce commit ;
3. construire le ZIP une seule fois avec un manifeste d’entrées ;
4. calculer le SHA-256 ;
5. publier exactement le même fichier ;
6. télécharger l’asset ;
7. exécuter `cmp` et `unzip -tq` ;
8. refuser toute gate obligatoire non `PASS` ;
9. générer l’attestation release depuis les preuves JSON ;
10. publier tag, run ID, commit, SHA, hashes d’entrées et résultats ;
11. limiter les permissions et exclure les secrets de production ;
12. vérifier les protections GitHub contre déplacement et suppression du tag ;
13. archiver la configuration de protection et l’approbation d’environnement ;
14. produire `documentation/release-approval.json` après décision humaine.

La signature GPG, SSH ou Sigstore est un contrôle distinct. Elle ne doit être affirmée que si `verified=true` et la chaîne de confiance sont vérifiées. Un tag unsigned peut être techniquement audité, mais ne doit pas être présenté comme signé.

## 12. Approbations et sign-off

Trois décisions sont obligatoirement séparées :

| Décision | Responsable | Périmètre |
|---|---|---|
| Technique | Responsable technique indépendant | architecture, code, sécurité, tests, performance, exploitation et release |
| UX/contenu | Reviewers UX et relecteurs natifs | design, accessibilité, responsive, RTL et langues |
| Commerciale | Responsable produit/client | périmètre vendu, prix, paiement, support et promesses |

Chaque décision doit contenir identité ou rôle, conflit d’intérêts, date UTC, version, commit, périmètre, réserves, preuves examinées, décision et signature. L’auteur du changement ne peut pas être l’unique approbateur. Une décision positive ne compense jamais un échec dans une autre colonne.

La release technique peut être créée par CI si le contrôle d’environnement le permet, mais le label Ultra-Premium et la certification absolue restent bloqués jusqu’à l’approbation des trois décisions. Le succès automatisé ne fabrique aucune signature humaine.

## 13. Definition of Done

Une version est recevable pour `TECHNICAL_RELEASE` uniquement si le thème et le core s’installent depuis un répertoire vide, la mise à jour depuis la version précédente conserve données et réglages, les routes renvoient les codes attendus, les familles FR/EN/AR sont cohérentes, HMAC et Semgrep sont exécutés réellement, SQL respecte son budget, les baselines et preuves correspondent au build, le package et l’asset sont identiques, aucun secret ni placeholder n’est présent, et les protections de release sont prouvées.

Une version est recevable pour `ULTRA_PREMIUM` uniquement si, en plus, la charge sur 1 000 annonces est `PASS`, les sauvegardes/restaurations/rollback sont testés, la revue UI/UX atteint ses seuils, les attestations linguistiques sont jointes, les schémas et matrices sont valides, aucun finding critique ou élevé ouvert ne subsiste, les trois décisions de sign-off sont positives et l’approbation indépendante de release est archivée.

**Un seul échec obligatoire interdit les textes `CERTIFIÉ`, `100 % conforme` et `ULTRA-PREMIUM`.**

## 14. Formulations autorisées

Si toutes les gates obligatoires, les artefacts, les preuves et les décisions sont positives :

> **CERTIFIÉ — toutes les gates obligatoires sont PASS, les artefacts correspondent au build testé, les preuves sont générées automatiquement et aucune limitation bloquante ne subsiste.**

Si la release est exploitable mais que le sign-off n’est pas terminé :

> **RELEASE CANDIDATE — fonctionnellement exploitable, mais sign-off incomplet.**

Si les gates techniques sont passées avec des limites explicitement excluantes :

> **CONFORME TECHNIQUEMENT SOUS RÉSERVES — les réserves sont listées et excluent la certification absolue.**

Si une gate obligatoire est `FAIL`, `NOT_RUN`, `NO_BASELINE`, `NON_REPRODUCIBLE` ou `BLOCKED` :

> **REFUS DE SIGN-OFF — une ou plusieurs gates obligatoires ne sont pas satisfaites.**

## 15. Checklist client

Le client reçoit le commit et tag vérifiés, l’URL de release, le ZIP, les SHA local et distant, l’attestation release, le manifeste d’entrées, l’environnement généré, les preuves JSON, les logs bruts, les captures et diffs visuels, les attestations natives, la matrice de code, les déviations, les runbooks d’installation, rollback, sauvegarde/restauration et la liste des limitations.

La signature client précise séparément si elle couvre fonctionnel, technique, sécurité, performance, linguistique, design/UI ou production. Une signature linguistique ne vaut pas approbation technique ; une signature technique ne vaut pas approbation juridique, commerciale ou d’infrastructure de production.

## 16. Amendements par rapport à v1.6

| Sujet | Amendement v1.7 |
|---|---|
| Provenance | Séparation obligatoire entre fichiers statiques versionnés et preuves générées CI ; suppression de l’auto-référence impossible. |
| Reproductibilité | Trois niveaux distincts ; `PACKAGE_REPRODUCIBLE` ne vaut pas automatiquement `SOURCE_TO_RELEASE_REPRODUCIBLE`. |
| Statuts | Suppression de l’ambiguïté `PARTIAL` ; statuts d’implémentation, test et décision séparés. |
| Labels | Séparation technique, UX/contenu, commerciale et Ultra-Premium. |
| Performance | Environnement de charge, cache, workers, 1 000 annonces, p50/p95/p99 et point de saturation obligatoires. |
| Opérations | RPO, RTO, restauration, rollback, rétention et incident simulé obligatoires pour Ultra-Premium. |
| Sécurité chaîne | SBOM, secrets, dépendances, rotation et provenance vendor ajoutés. |
| Release | Attestation générée, vérification de protection du tag, approbation indépendante et `cmp` obligatoires. |
| UX et langues | Trois reviewers UX minimum et attestations natives structurées, séparées de la validation technique. |

## Références

[1]: https://www.w3.org/TR/WCAG22/ "W3C — Web Content Accessibility Guidelines 2.2"
[2]: https://developer.wordpress.org/apis/security/ "WordPress Developer Resources — API security"
[3]: https://developer.wordpress.org/plugins/security/data-validation/ "WordPress Developer Resources — data validation and escaping"
[4]: https://owasp.org/www-project-application-security-verification-standard/ "OWASP ASVS"
[5]: https://semgrep.dev/docs/ "Semgrep documentation"
[6]: https://web.dev/vitals/ "Google web.dev — Core Web Vitals"
