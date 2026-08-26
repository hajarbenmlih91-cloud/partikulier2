# Remédiation performance technique — 2026-08-26

## Périmètre

Cette remédiation vise uniquement le coût d’exécution serveur et le bootstrap REST. Aucun template, CSS, JavaScript visuel, texte public, URL front ou structure de carte n’a été modifié.

## Modifications

`partikulier-core/src/RestController.php` construit désormais les services à la demande. Une requête GET publique de collection ne construit plus les services d’écriture, leads, favoris et santé avant leur utilisation.

`partikulier-core/partikulier-core.php` conserve les classes communes et ne charge les classes REST/écriture que pour REST, WP-CLI ou l’administration. Les contrats CLI et REST restent couverts.

`theme/functions.php` ne charge aucun module de thème sur la route exacte `/wp-json/partikulier/v1/listings` ou `/index.php?rest_route=/partikulier/v1/listings`. Cette route ne rend pas de HTML. Les routes front et les autres routes REST ne sont pas concernées.

## Contrôles locaux

| Contrôle | Résultat |
| --- | --- |
| Lint PHP des répertoires core/theme/scripts/mu-plugins | PASS — 66 fichiers, aucune erreur |
| PHPStan borné | PASS — 0 erreur, limite 1024 Mo |
| PHPCS sécurité | PASS — 0 erreur ; 8 avertissements historiques/intentionnels |
| `scripts/check.sh` | PASS — toutes les régressions et R6 |
| Semgrep projet | PASS — 0 finding bloquant sur 66 cibles |
| Semgrep `p/default` | 0 finding ; 14 avertissements de parsing sur anciens rapports JSON historiques, aucun finding de sécurité |

## Déploiement staging

Le plugin Partikulier Core a été mis à jour sur le staging depuis l’archive `partikulier-core-lazy-2026-08-26.zip`, SHA-256 `c3d623cdbc960d55399f04b4e4882569cae2441e86d195f8bc4346c76cd8d6b2`. WordPress a confirmé la mise à jour et la liste des extensions confirme Partikulier Core actif.

Le test WP Super Cache a été installé, activé puis supprimé après mesure négative. Il n’a pas amélioré le TTFB et entrait en conflit avec LiteSpeed. Le staging conserve donc LiteSpeed Cache seul parmi les extensions de cache.

## Vérification REST live

Le endpoint `GET /wp-json/partikulier/v1/listings?locale=fr&order=newest&page=1&per_page=24` répond HTTP 200, JSON valide, avec exactement les clés `data` et `page`, 21 lignes et `page=1` sur cinq requêtes consécutives.

Les TTFB observés après déploiement du core optimisé sont : 3,667 s, 3,467 s, 3,257 s, 3,677 s et 2,823 s. Le HCDN reste `MISS` sur les quatre premières requêtes et `HIT` sur la cinquième. Le seuil contractuel `<800 ms` n’est donc pas atteint. Cette mesure indique que le bypass de bootstrap préserve le contrat mais ne suffit pas à corriger le délai imposé par l’infrastructure HCDN/WordPress.

## Décision technique

La remédiation est déployée et sans changement de design. Elle ne justifie pas un PASS performance : le prochain levier doit être un profilage serveur Hostinger/PHP-FPM/MariaDB ou une configuration de cache HTML au niveau réellement servi par HCDN, accessible avec les droits hPanel appropriés.
