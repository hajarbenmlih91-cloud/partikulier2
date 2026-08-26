# Rapport de clôture — CDC v1.7.1 / candidat final26

**Date :** 26 août 2026 UTC
**Projet :** thème WordPress Partikulier
**Environnement :** WordPress de test Hostinger, Estatik 4.3.4, Polylang FR/EN/AR
**Branche GitHub :** `automation/release-approval-gate-v1.7.1`
**Commit code de référence :** `11eec5064eef0aefdd8cbebc3400e1423fe0c050`
**Archive live final26 :** `partikulier-cdc-v18-final26.zip`, SHA-256 `78db7a24033c943add89c0efe1ec1b143c589108a48ba803ec3cf6dd04c7eead`

> **Verdict global : NO-GO provisoire.** Les corrections principales sont déployées et plusieurs gates automatisés passent, mais la performance contractuelle, le scan Semgrep complet, l’acceptation froide CI, la capacité, l’upgrade/rollback et les signoffs humains ne sont pas tous PASS. Il serait incorrect de présenter le thème comme certifié ou pleinement conforme au CDC.

## 1. Résumé exécutif

Le candidat final26 a été installé et activé sur le WordPress de test autorisé. Le filtre transactionnel public est maintenant aligné sur la taxonomie Estatik observée dans l’administration : `es_status` pour vendre/louer, tandis que `es_category` reste réservée aux termes historiques de villes et aux contrôles d’assainissement. Le filtre public `?es_action=a-vendre` sélectionne 21 cartes et le smoke détecte 20 badges `À VENDRE`; le dataset live ne fournit pas de preuve équivalente pour une annonce en location.

Les vérifications publiques bornées passent sur Chromium, Firefox et WebKit pour les archives FR/EN/AR, le filtre vente, la popup mobile, le retour du focus, le JSON-LD AR et le chargement différé des images. Le gate axe régénéré sur le SHA `a4838531c6bdbbbebda6d564e92d632c22af67b2` passe 6/6 scénarios avec zéro violation axe. Ces résultats automatisés ne remplacent toutefois ni une revue WCAG humaine ni une validation éditoriale native arabe.[1]

## 2. Tableau de qualification

| Domaine | Résultat | Preuve et portée | Limite ou action restante |
| --- | --- | --- | --- |
| Déploiement WordPress final26 | **PASS** | ZIP installé et thème activé sur le site de test ; archive intègre `Version: 6.17.18` et son SHA est enregistré ci-dessus. | Le correctif back-office `openssl_decrypt` et la correction d’assertion CI ont été poussés après le ZIP live ; ils ne sont pas inclus dans cette archive live. |
| GitHub candidate | **PASS** | Branche candidate synchronisée au commit `11eec50`.[2] | La publication GitHub ne vaut pas approbation de release. |
| Accessibilité automatisée | **PASS partiel** | Axe FR/EN/AR desktop/mobile : 6/6, zéro violation, zéro bouton/champ sans libellé, zéro image sans alt.[3] | Revue humaine WCAG et tests utilisateurs : **NOT RUN**. |
| Popup filtres mobile | **PASS** | `aria-hidden`, focus, Échap et retour du focus passés sur les trois moteurs dans le smoke final26.[4] | Aucun signoff humain indépendant. |
| UI mobile archive | **PASS automatisé** | Une carte par ligne et absence de débordement aux largeurs 320/360/375/390 ; 21 cartes détectées. | Revue visuelle humaine indépendante : **NOT RUN**. |
| Filtre vente | **PASS** | `es_action=a-vendre` sélectionné ; 21 cartes, 20 badges `À VENDRE` sur le dataset public actuel.[4] | Tests exhaustifs cumulés action/type/ville/budget : **NOT RUN**. |
| Filtre location | **BLOCKED / dataset** | Le smoke conserve l’observation `a-louer`, mais le dataset live ne démontre pas une annonce de location publiée et l’admin n’expose que `À vendre` dans `es_status`. | Ne pas créer de fausse annonce ; importer ou publier une vraie fixture métier séparément si une preuve rental est requise. |
| JSON-LD archive FR/EN/AR | **PASS automatisé** | Graphes ItemList et localisations contrôlés par le smoke. | Validation Rich Results externe et revue SEO humaine : **NOT RUN**. |
| JSON-LD fiches AR | **PASS automatisé** | Trois fiches AR distinctes : HTTP 200, `lang=ar`, `dir=rtl`, JSON-LD avec nom arabe et absence des chaînes françaises contrôlées.[5] | Contrôle linguistique natif : **NOT RUN**. |
| Images lazy-load | **PASS fonctionnel borné** | 21 images, invalides 0 après défilement borné, sans overflow sur les trois moteurs.[4] | Le poids global AR reste excessif ; cela ne valide pas la performance contractuelle. |
| Performance TTFB/LCP | **FAIL** | Mesures live curl : TTFB environ 2,76–3,15 s sur FR/EN/AR ; la cible contractuelle cache chaud `<800 ms` n’est pas atteinte. Les mesures navigateur précédentes montrent environ 490 Ko de ressources FR contre environ 2,21 Mo AR.[6] | Diagnostiquer l’infrastructure/cache Hostinger et le poids des images/fonts AR ; aucune déclaration PageSpeed ou LCP `<2,5 s`. |
| Semgrep ciblé | **PASS** | Périmètre corrigé : 0 finding après traitement du retour `false` d’`openssl_decrypt`.[7] | Ce n’est pas un scan global. |
| Semgrep projet complet | **FAIL** | Ruleset `p/default`, 357 fichiers : 49 findings bloquants, exit 1.[7] | Revue et correction de l’ensemble des findings ; aucun masquage par commentaire global. |
| Syntaxe et reproductibilité locale | **PASS** | `scripts/check.sh`, syntaxe PHP/JS, `npm ci --dry-run` root/theme et `git diff --check` passent dans le dernier gate local. | PHPStan/WPCS complets non exécutés. |
| CI GitHub | **NOT PASS / EN COURS** | Le run de référence `32925666336` passe les gates statiques, dépendances/SBOM, Semgrep CI, secret scan et rapport de qualification ; l’acceptation froide MariaDB/WordPress est encore `in_progress` à la dernière observation.[8] | Attendre la conclusion complète du job ; ne pas appeler CI vert avant succès de toutes les étapes. |
| Capacité 10 RPS / 900 s | **NOT RUN / CI EN COURS** | Le script prévoit les phases contractuelles mais le run froid n’a pas encore produit de rapport final exploitable. | Exécuter et archiver la télémétrie cgroup dans un environnement froid ; ne pas charger Hostinger live. |
| Upgrade / rollback | **NOT RUN** | Aucun test destructive d’upgrade/rollback validé dans cette série. | Exécuter sur stack isolée avec sauvegarde et vérification d’idempotence. |
| PHPStan / WPCS | **BLOCKED** | PHPStan n’est pas configuré dans le dépôt ; un essai historique avec stubs a dépassé la mémoire disponible. WPCS n’a pas été installé/configuré. | Ajouter versions, configs, baseline et seuils puis exécuter dans CI avec mémoire bornée. |
| Nettoyage staging | **DRY-RUN uniquement** | Aucun objet supprimé. 155/157/159 sont une chaîne FR/EN/AR publique ; 153/154 sont des brouillons vides ; 156/158 et 23 sont absents ; 21 est la page fonctionnelle « Déposer une annonce » ; 22 nécessite une identification par type/capacité.[9] | Confirmation explicite et plan d’impact indispensables avant toute suppression. |
| Revue humaine indépendante | **NOT RUN** | L’agent ne peut pas produire un signoff humain indépendant. | Faire relire par des personnes distinctes pour UX, arabe natif, sécurité et exploitation. |

## 3. Modifications réellement publiées

Le code candidat contient le bootstrap popup précoce, la gestion `inert`/ARIA et du focus, les landmarks et niveaux de titres corrigés, le JSON-LD localisé, le lazy-load contrôlé, les lockfiles root/theme, la configuration Playwright multi-navigateurs, la taxonomie transactionnelle `es_status`, la fixture de contrat vente/location, ainsi que la documentation de reprise. Les scripts de traduction, le formulaire, les cartes, les fiches Estatik, le SEO, le diagnostic et les traitements Polylang ont été alignés sur cette distinction entre statut transactionnel et villes historiques.

Le ZIP live final26 correspond à l’état déployé avant le correctif ultérieur du déchiffrement back-office et avant la dernière correction CI de l’assertion d’upgrade. Ces deux corrections sont présentes dans GitHub au commit `11eec50`, mais une nouvelle archive WordPress devrait être construite et vérifiée si elles doivent également être activées sur le site de test.

## 4. Données staging et absence de nettoyage

Le dry-run montre que l’ID 155 n’est pas un simple artefact isolé : il est publié et public, avec les traductions anglaise 157 et arabe 159 également publiées et publiques. Le supprimer modifierait la visibilité d’une annonce actuellement rendue dans l’archive. Les IDs 153 et 154 sont des brouillons FR vides, tandis que 156 et 158 n’existent plus dans l’éditeur. L’ID 21 est la page publique « Déposer une annonce ». Les IDs 22 et 23 ne peuvent pas être considérés comme supprimables sur la seule base de `post.php` : 22 renvoie une restriction de type/capacité et 23 un contenu inexistant.[9]

> **Aucune annonce, média, terme, traduction ou donnée métier n’a été créée, supprimée ou modifiée pendant cet inventaire.**

## 5. Actions nécessaires avant un GO

Le premier blocage est la performance : il faut séparer le diagnostic Hostinger/cache du poids spécifique AR, puis obtenir des mesures froides et chaudes reproductibles avec TTFB et LCP. Le second est la qualification CI : attendre le run `32925666336` et archiver son résultat complet, notamment capacité et upgrade. Le troisième est le Semgrep global, qui reste en échec avec 49 findings. Enfin, il faut une vraie fixture rental contrôlée dans un environnement isolé, une configuration PHPStan/WPCS versionnée, un dry-run de rollback et des signoffs humains indépendants.

La suppression staging ne doit pas être exécutée à ce stade. Elle exige une confirmation dédiée après identification de l’ID 22, validation de la relation Polylang 155/157/159 et sauvegarde vérifiable. Cette opération est distincte de la correction du thème et ne doit pas servir à faire passer un gate.

## Références

[1]: https://blanchedalmond-reindeer-376379.hostingersite.com/fr/annonces/ "Archive publique FR final26"
[2]: https://github.com/hajarbenmlih91-cloud/partikulier2/tree/automation/release-approval-gate-v1.7.1 "Branche GitHub candidate"
[3]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/11eec5064eef0aefdd8cbebc3400e1423fe0c050/documentation/accessibility-live-final26-final.json "Preuve axe final26"
[4]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/11eec5064eef0aefdd8cbebc3400e1423fe0c050/documentation/live-final26-smoke-chromium.json "Smoke final26 Chromium"
[5]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/11eec5064eef0aefdd8cbebc3400e1423fe0c050/documentation/live-final26-ar-properties-chromium.json "Contrôle JSON-LD de trois fiches AR"
[6]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/4a12222887e8be91a72a033762c5df70e998d1e1/documentation/performance-live-final26-2026-08-26.log "Mesures performance live final26"
[7]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/11eec5064eef0aefdd8cbebc3400e1423fe0c050/documentation/semgrep-targeted-final26-2026-08-26.log "Semgrep ciblé final26"
[8]: https://github.com/hajarbenmlih91-cloud/partikulier2/actions/runs/32925666336 "Run CI final26 de référence"
[9]: https://github.com/hajarbenmlih91-cloud/partikulier2/blob/91651ba38728d7e6190dd53fe31f6f5bb4ba5cf7/documentation/staging-cleanup-dry-run-2026-08-26.md "Dry-run nettoyage staging"
