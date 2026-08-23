# Revue de code senior — candidate v6.17.11

**Auteur :** Manus AI  
**Base de comparaison :** `v6.17.7` — commit `6153debac8f84da46b1da95af1c810320dc7e5bf`  
**Parent fonctionnel :** release `v6.17.10` — commit `85d59798b0ea0c9bf93f32ec7d02c883f3831493`  
**Périmètre :** diff complet du thème WordPress, mu-plugin, scripts de provisioning et de recette, contrats, preuves, packaging et CI. La revue inclut les corrections ajoutées après la release v6.17.10 et ne modifie pas le tag v6.17.7.

## Verdict exécutif

La revue a confirmé plusieurs défauts réels dans la candidate précédente : dérive du slug de dépôt entre le provisioning et les templates, risque de mise en cache d’espaces privés, installateur insuffisamment strict, sorties JSON mélangées à des résumés humains, absence de suivi multi-hop des redirections et preuve de tri initialement non probante. Ces défauts ont été corrigés dans le working tree v6.17.11 et les contrôles froids correspondants passent.

> **Verdict : corrections techniques acceptées sous réserve ; sign-off CDC fermé inconditionnel refusé à ce stade.**

La réserve ne vient pas d’un échec observé dans les contrôles froids, mais de deux limites de gouvernance de livraison qui restent ouvertes : le workflow GitHub ne rejoue pas la recette WordPress/HTTP/HMAC/SQL complète, et la source Estatik reste téléchargée depuis une URL générique puis vérifiée par version plutôt que par un artefact versionné et un checksum. La release v6.17.11 doit donc être présentée comme une candidate corrigée et auditée, non comme une certification absolue de reproductibilité de la chaîne externe.

## Méthode de revue

Chaque modification a été examinée au niveau du hunk et de la fonction appelée. L’analyse a porté sur les effets de bord WordPress, l’ordre des hooks, Polylang, le cache avant bootstrap, les entrées GET, les secrets, la gestion d’erreur Bash, le suivi HTTP, la validité JSON et la cohérence entre source, preuves et bundle. Les tests ont été rejoués sur une instance froide contenant WordPress 7.1, PHP 8.3.6, MariaDB 10.11.14, Estatik 4.3.4, Polylang 3.8.7, Query Monitor 4.0.7, Node 22.13.0, Playwright 1.62.1 et 30 propriétés publiées.

La release existante v6.17.10 reste immuable. Son asset GitHub officiel est distinct de l’ancien ZIP theme-only contrôlé précédemment : l’asset actuel v6.17.10 avait le SHA-256 `262dbbb12ab94552edbbf19e6ca410110c840509b6b635525012b33c232c3c84`, alors que l’ancien fichier utilisateur avait le SHA `9173c97b…`. Cette revue porte sur le source et les artefacts v6.17.11, pas sur cet ancien ZIP.

## Tableau priorisé des constats

| Priorité | Constat et localisation | Impact | Statut et preuve |
|---|---|---|---|
| **Majeur — corrigé** | Le canonical deposit était `deposer-une-annonce` dans les pages requises et plusieurs templates, alors que le contrat Polylang attendait `/deposer/`, `/deposer-en/` et `/deposer-ar/`. Voir `class-required-pages.php` lignes 34–51, 66–98 et les liens des templates. | Une page peut exister avec un slug différent de celui utilisé par les liens, le SEO, le sitemap et Estatik ; cela crée des 404 ou des URLs incohérentes après activation. | Le registre utilise `deposer`, les liens passent par `pk_page_url('deposer', '/deposer/')`, et la migration idempotente reconnaît l’ancien slug. Le contrat et l’E2E passent **16/16** [1] [2]. |
| **Majeur — corrigé** | `find()` indexait directement `self::pages()[$slug]` sans garde, et aucune migration explicite ne traitait un site déjà installé avec l’ancien slug. | Une clé legacy inconnue pouvait produire un avis PHP ; un upgrade pouvait conserver l’ancien permalien sans rétablissement canonique. | `find()` sanitise et garde les slugs inconnus ; `maybe_migrate_legacy_slugs()` migre la page historique vers `deposer` une seule fois. Test sur page renommée : slug canonique présent, legacy absent, **PASS** [1]. |
| **Majeur — corrigé** | Le cache pouvait considérer des pages localisées privées comme publiques au moment précoce `after_setup_theme`. | Un HTML de dépôt ou d’espace personnel pouvait être servi ou persisté dans le cache partagé, avec risque de fuite d’état ou de contenu utilisateur. | `is_private_path()` couvre les variantes FR/EN/AR et legacy ; `is_cacheable_request()` les exclut avant capture. Douze GET sur six routes et deux passages n’ont produit aucun `X-Partikulier-Cache` ni fichier HTML, **PASS** [3]. |
| **Majeur — corrigé** | `install.sh` utilisait un mode Bash non strict, des identifiants admin fixes, un secret HMAC de développement en dur et interpolait les variables SQL sans validation/échappement. | Une erreur silencieuse pouvait laisser une installation partielle ; le mot de passe et le secret de recette étaient prévisibles ou exposés dans le source. | `set -Eeuo pipefail`, validation de `DB_NAME`/`DB_USER`, échappement SQL du mot de passe, variables admin configurables et secret aléatoire sont maintenant appliqués. Une installation froide avec un mot de passe contenant une apostrophe a réussi ; un nom DB invalide sort en code 2 [4]. |
| **Majeur — corrigé** | L’installateur d’outils téléchargeait le dernier WP-CLI indépendamment de `WPCLI_VERSION`, exécutait `npm install` conditionnellement et déclarait Node 22 sans le contrôler. | Une recette pouvait dériver dans le temps ou utiliser un arbre npm différent du lockfile. | WP-CLI 2.12.0 est téléchargé depuis son artefact versionné et vérifié par SHA-512 ; Node majeur 22 est contrôlé ; `npm ci` et `npx --no-install playwright` sont utilisés [5]. |
| **Majeur — corrigé** | `routes-contract.mjs` ne suivait qu’un seul hop et écrivait le JSON puis un résumé humain sur stdout. | Une chaîne de redirections ou une boucle pouvait passer inaperçue ; une capture stdout n’était pas un JSON parseable. | Le harnais suit jusqu’à huit hops, détecte boucle/sortie externe, conserve `status` initial, `terminal_status`, `final_path` et `chain`. Le résumé est sur stderr ; JSON strict et contrat **16/16** [2]. |
| **Majeur — corrigé** | Le test de tri initial pouvait conclure sur une seule annonce filtrée par Polylang, ce qui rendait la monotonie mathématiquement vacueuse. | La fonctionnalité `pk_order` pouvait être déclarée conforme sans comparer une série significative. | `test-search-sorting.php` force une requête principale, neutralise la sélection de langue uniquement pour la fixture, exige au moins six lignes et vérifie trois ordres. Résultat : **24 lignes**, prix ascendant, prix descendant et surface descendante, tous **PASS** [6]. |
| **Moyen — corrigé** | Le test visuel suivait les pages protégées vers `wp-login.php` mais ne reportait pas explicitement l’URL finale. | Une capture de login pouvait être interprétée à tort comme une capture de la page « Mes annonces ». | Le harnais vérifie et consigne `final_path`; les six vues protégées indiquent `/wp-login.php`. Les 30 scénarios restent **30/30** [7]. |
| **Moyen — corrigé** | `audit.mjs` conservait `/property/` et `/deposer-une-annonce/`, alors que le contrat courant utilise les routes localisées. | Un opérateur pouvait lancer un ancien audit et obtenir des résultats contradictoires avec la livraison. | Le script charge `tests/routes-contract.json` et choisit les routes FR canoniques. Audit relancé sur accueil/annonces/dépôt : zéro 404 et zéro erreur JavaScript [8]. |
| **Majeur — ouvert : assurance CI** | `.github/workflows/cdc-v6.17.11.yml` exécute npm, syntaxe, compte de baselines, Semgrep et packaging, mais ne provisionne pas WordPress/MariaDB et ne rejoue pas HTTP, navigateur, HMAC, SQL ou cache. | Un commit peut être vert dans GitHub alors qu’une régression d’environnement froid n’est pas détectée. Le nom « CDC fermé » suggère une couverture plus large que la couverture réelle. | La CI a été rendue plus explicite et vérifie la validité des JSON et du bundle, mais la recette complète reste une preuve locale/rejouée séparément. **Action recommandée :** ajouter un job d’acceptance avec environnement froid, ou renommer clairement le job en static/package et rendre la procédure externe obligatoire [9]. |
| **Moyen — ouvert : supply chain Estatik** | `install.sh` demande 4.3.4 mais conserve `https://downloads.wordpress.org/plugin/estatik.zip` car l’URL versionnée testée renvoie 404 ; la version installée est ensuite contrôlée. | Le ZIP obtenu peut changer sans que la recette soit bit-à-bit reproductible, même si sa version déclarée reste 4.3.4. | Limitation documentée dans `environment-v6.17.11.json`. **Action recommandée :** publier/mirrorer l’artefact exact et vérifier son SHA-256 avant installation [4] [10]. |
| **Mineur — ouvert** | Plusieurs fichiers historiques de documentation contiennent encore l’ancien slug ou des formulations antérieures. | Risque de confusion documentaire, sans impact sur le code actif ni le bundle v6.17.11 contrôlé. | Les références historiques doivent être marquées « archive » ou mises à jour lors d’un nettoyage documentaire dédié. |
| **Mineur — ouvert** | L’indentation PHP reste hétérogène dans des modules anciens, notamment `class-localization.php` et `class-cache.php`. | Dette de maintenabilité et revue plus coûteuse ; pas de défaut fonctionnel établi dans cette campagne. | À traiter par un passage PHPCS/formatage séparé, sans mélange avec une release fonctionnelle. |

## Résultats de contrôles

| Contrôle | Résultat v6.17.11 | Artefact |
|---|---:|---|
| Syntaxe PHP thème + mu-plugin | 66 fichiers, 0 erreur | `documentation/check-v6.17.11.log` |
| Syntaxe JavaScript | 15 scripts, 0 erreur | `documentation/check-v6.17.11.log` |
| Contrat routes direct + chaîne terminale | 16/16 | `documentation/routes-contract-v6.17.11.json` |
| E2E Playwright en contextes frais | 16/16 | `documentation/e2e-v6.17.11.json` |
| Régression visuelle | 30/30, seuil 0,5 % | `documentation/visual-v6.17.11.json`, `tests/baselines-6.17.11/` |
| Négociation navigateur/cookie/robots | 10/10 | `documentation/browser-detection-v6.17.11.json` |
| Police arabe et RTL | 3/3 | `documentation/i18n-fonts-v6.17.11.json` |
| Famille i18n | FR/EN/AR non vide | `documentation/discover-i18n-family-v6.17.11.json` |
| Tri prix/surface | 3 ordres × 24 résultats | `documentation/search-sorting-v6.17.11.json` |
| Cache privé | 12 GET, aucun HIT/fichier privé | trace de revue et code `class-cache.php` |
| HMAC HTTP concurrent | 5 rondes, 5 faux/5 duplicate, 4×401 | `documentation/hmac-http-v6.17.11.json` |
| SQL archive | 49, 45, 45 requêtes, seuil 56 | `documentation/sql-v6.17.11-summary.json` et trois traces |
| Semgrep | 66 fichiers, 704 437 octets, 0 finding bloquant | `documentation/semgrep-v6.17.11.json` |
| Package | bundle contrôlé par unzip et manifestes internes | `partikulier-6.17.11.zip` et log package |

Les contrôles de sortie JSON ont également été vérifiés avec `jq empty`. Les fichiers routes, E2E, visual, i18n, HMAC, SQL et Semgrep de la candidate sont donc parseables individuellement ; les résumés humains des harnais sont envoyés sur stderr et ne contaminent plus une capture stdout destinée à devenir une preuve JSON.

## Recommandations de clôture

La prochaine décision ne doit pas être formulée comme « tout est certifié ». Pour obtenir un sign-off CDC fermé, il faut d’abord faire évoluer la CI afin qu’elle rejoue réellement la recette froide ou qu’elle porte un nom et une documentation qui limitent explicitement son assurance à static/package. Il faut ensuite figer l’artefact Estatik 4.3.4 par miroir ou checksum. Ces deux sujets sont indépendants des contrôles locaux réussis et doivent rester visibles dans la release notes.

En dehors de ces réserves, les défauts techniques reproduits pendant la revue ont été traités, testés et embarqués dans les chemins v6.17.11. Le tag `v6.17.7` n’a pas été réécrit et la release v6.17.10 reste inchangée.

## Références

[1]: ../theme/inc/class-required-pages.php "Registre et migration des pages requises"
[2]: ../scripts/routes-contract.mjs "Contrat HTTP et suivi des redirections"
[3]: ../theme/inc/class-cache.php "Cache public et exclusions privées"
[4]: ../scripts/install.sh "Provisioning WordPress froid"
[5]: ../scripts/install-tooling.sh "Installation versionnée des outils"
[6]: ../scripts/test-search-sorting.php "Test d’intégration des tris"
[7]: ../scripts/visual.mjs "Harnais visuel et URL finale"
[8]: ../scripts/audit.mjs "Audit fonctionnel général"
[9]: ../.github/workflows/cdc-v6.17.11.yml "Workflow CI de la candidate"
[10]: documentation/environment-v6.17.11.json "Preuve d’environnement et limite Estatik"
