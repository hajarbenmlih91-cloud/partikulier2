# Plan d’action senior — candidate `a58942c`

**Date :** 27 août 2026 UTC
**SHA final suivi :** `a58942ceb35aa75bb02e7c547bc3d4fdaffa5e39`
**Branche :** `automation/functional-release-2ff8e38`
**Staging autorisé :** `https://blanchedalmond-reindeer-376379.hostingersite.com`
**Production :** interdite dans cette qualification.

## Décision de départ

Le statut reste **NO-GO**. Le code PHP-only `a58942c` est déployé et les routes publiques répondent, mais le TTFB contractuel, la capacity, la non-régression vente, les baselines officielles, le package CI et les signoffs ne sont pas tous PASS. Aucun seuil ne doit être abaissé et aucun nouveau run CI ne doit être lancé uniquement pour chercher un meilleur chiffre.

Le design, les templates, le CSS, les assets, les textes publics, le responsive, le RTL et l’UX sont verrouillés. Toute correction doit être PHP/runtime/données strictement justifiée et comparée avant/après.

## 1. Mesure TTFB client détaillée

Le script `scripts/measure-ttfb-breakdown-v1.0.py` mesure chaque route avec une requête `curl` indépendante, HTTP/1.1, sans cookie ni authentification, avec `Cache-Control: no-cache, no-store`, `Pragma: no-cache` et un paramètre unique `pk_ttfb_probe`. Il enregistre pour chaque échantillon : résolution DNS, connexion TCP, handshake TLS, pré-transfert, attente serveur, TTFB, téléchargement, durée totale, code HTTP, IP distante, URL finale, région, timestamp et headers HCDN/LiteSpeed.

Les routes contractuelles sont `/fr/`, `/fr/annonces/`, `/wp-json/partikulier/v1/listings?locale=fr&per_page=24` et `/wp-json/partikulier/v1/`. Le script produit 20 échantillons par route et calcule p50, p95, p99 et maximum pour DNS, TCP, TLS, attente serveur, TTFB et total. Le champ `server_wait_ttfb_seconds` est calculé comme `time_starttransfer - time_pretransfer`; il ne doit pas être confondu avec `x-hcdn-upstream-rt`, qui est une mesure HCDN distincte.

La première exécution sur `a58942c`, le 27 août 2026 à 03:16 UTC depuis `sandbox-unknown`, a donné 20/20 HTTP 2xx par route. Le TTFB p95 client est de 5,712 s pour la home FR, 6,435 s pour l’archive FR, 5,049 s pour REST listings et 6,713 s pour REST root. Le TLS p95 est de 3,983–4,735 s et l’attente serveur p95 de 2,303–3,621 s. Les réponses indiquent HCDN/LiteSpeed MISS. L’écart de deux secondes n’est donc pas imputable au seul temps upstream HCDN; il est réparti entre handshake TLS/chemin proxy et attente avant le premier octet. Ces chiffres restent **FAIL** par rapport au seuil p95 <800 ms.

### Protocole de séparation à demander à Hostinger

Hostinger doit fournir, sur une même fenêtre temporelle, les timestamps ou métriques disponibles pour DNS edge, acceptation TCP, handshake TLS, entrée dans HCDN, sortie HCDN vers l’origine, réception du premier octet origine, premier octet client et fin de transfert. Il faut aussi relever le protocole négocié, la réutilisation de connexion, la compression, le keep-alive, l’état HCDN/LiteSpeed et la signification exacte de `x-hcdn-upstream-rt`.

Aucune correction ne doit être appliquée sur la base d’un simple « serveur normal ». Un changement ne sera accepté que s’il est réversible, staging-only, mesuré avant/après et compatible avec les routes privées, authentifiées, d’écriture et de soumission qui doivent rester hors cache public.

## 2. Diagnostic sûr du chemin `es_status` vente

Le filtre PHP ne doit pas être élargi tant que la donnée n’est pas inventoriée. L’audit doit comparer une seule fixture de vente temporaire avec une annonce réelle, sans modifier cette dernière. Pour chaque annonce, relever ID, statut de publication, langue Polylang, relation de traduction, `es_status`, slug du terme, `es_type`, URL canonique, URL locale et réponse publique.

La taxonomie canonique attendue est `es_status`. Le slug source `a-vendre` doit être vérifié dans l’administration et dans les tables de relation, sans supposer qu’un libellé traduit (`a-vendre-fr`, `a-vendre-ar`) est interchangeable avec le slug source. Il faut également vérifier si le filtre Estatik ou Polylang remappe le terme par langue avant la requête finale.

Si l’accès Action Scheduler reste refusé, l’audit de base de données doit rester en lecture seule dans phpMyAdmin. Les requêtes autorisées sont `SHOW TABLES LIKE '%actionscheduler_actions'` puis des `SELECT` sur les statuts, hooks, groupes et logs; aucun `DELETE`, `UPDATE`, `TRUNCATE`, lancement manuel ou purge ne doit être effectué. Cette file est un axe TTFB séparé du chemin `es_status`.

La fixture vente ne doit être publiée que si nécessaire, avec titre non ambigu, sans média et avec une seule langue ou une chaîne Polylang explicitement contrôlée. Le résultat exigé est : `a-vendre` affiche la fixture dans la locale attendue, le badge et l’URL sont corrects, le JSON-LD est cohérent, `a-louer` n’affiche pas la vente, une action inconnue donne zéro, puis la fixture est déplacée à la corbeille et supprimée définitivement avec audit des seuls ID concernés.

Si l’annonce réelle possède le mauvais ou aucun `es_status`, la correction est une décision de données/dataset, pas une extension du filtre PHP. Si les données sont correctes mais la requête finale exclut la fixture, ajouter d’abord un test ciblé et corriger uniquement le mapping démontré.

## 3. Capacity

La CI officielle reste la référence. Le runtime doit être reproduit avec les mêmes seuils, durée, quatre CPU visibles, PHP-FPM/LSAPI, opcache, rate limiting et protections. Les seuils restent : sustained 10 RPS PASS, burst 25 RPS à au moins 24,75 RPS, p95 ≤1,5 s, p99 ≤3,0 s, CPU ≤80 %, writes 2 RPS PASS et probe 50 RPS conforme. `worker_connections 2048` est conservé; le rate limiting ne doit pas être supprimé.

Le profilage doit séparer bootstrap WordPress, hooks, SQL, Estatik, Polylang, PHP-FPM/LSAPI, files d’attente, APCu/transients, cache objet, limites CPU/RSS et instrumentation. Une correction ne sera gardée que si elle améliore le résultat dans le même runtime sans casser les tests fonctionnels.

## 4. Ordre de validation finale

L’ordre obligatoire est : mesure TTFB détaillée; diagnostic Hostinger; inventaire `es_status`; correction justifiée éventuelle; contrôles locaux; requalification TTFB/capacity/vente; capture des 30 baselines exactes; manifeste réel `tests/baselines-6.17.22/SHA256SUMS`; revue indépendante; une seule CI complète sur le SHA final; package/release CI non SKIPPED; upgrade; rollback; signoffs humains; verdict.

Les commandes locales minimales sont :

```bash
bash scripts/check.sh
find partikulier-core theme -name '*.php' -print0 | xargs -0 -n1 php -l
vendor/bin/phpstan analyse partikulier-core theme/inc/class-search-filters.php --no-progress
git diff --check
git diff --cached --check
```

Une preuve locale ne remplace pas le package CI. Une revue de captures ne remplace pas une baseline officielle. Une métrique favorable isolée ne remplace pas le contrat complet.

## Critère de décision

Le verdict est **GO uniquement si tous les gates sont PASS sur `a58942c`** : code/diff, staging, location, vente, TTFB, capacity, UI Chromium/Firefox/WebKit, baselines et manifeste, package CI, upgrade, rollback et signoffs. Tout `FAIL`, `BLOCKED`, `INCONCLUSIF`, `NOT RUN` ou non-prouvé maintient **NO-GO**.
