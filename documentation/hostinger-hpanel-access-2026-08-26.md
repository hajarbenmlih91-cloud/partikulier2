# Vérification hPanel Hostinger — 2026-08-26

La navigation vers `https://hpanel.hostinger.com/` a renvoyé l’application hPanel mais un écran vide, sans élément interactif ni session exploitable dans cette session de travail. Le HTML capturé contient la coquille de l’application et des références au dashboard, mais aucune interface utilisable pour atteindre les réglages du site ou du cache.

Aucun réglage Hostinger, cache serveur, CDN ou QUIC.cloud n’a donc été modifié depuis hPanel. Le TTFB reste à traiter par une capacité accessible depuis WordPress/test ou par une action ultérieure de l’administrateur Hostinger.

La page LiteSpeed Cache du staging est accessible. Elle affiche LiteSpeed Cache 7.9, cache de page activé, cache privé activé, et indique que certaines fonctions/mesures nécessitent LSWS/OpenLiteSpeed/ADC ou QUIC.cloud. Aucune option n’a été enregistrée et aucune connexion QUIC.cloud n’a été activée.

Un test réversible de WP Super Cache (plugin officiel Automattic) a été lancé depuis le répertoire WordPress afin de vérifier une couche de page-cache HTML hors bootstrap du thème. Aucun réglage de thème ou de design n’est modifié par ce test.

WP Super Cache 3.1.1 a été installé et l’extension est activée, mais sa mise en cache n’est pas encore activée. WordPress/LiteSpeed affiche un avertissement de conflit entre WP Super Cache et LiteSpeed Cache. Aucun réglage de cache n’a encore été enregistré.

La page WP Super Cache affiche actuellement « Mise en cache désactivée » et le mode recommandé « Mise en cache activée ». La configuration n’a pas encore été soumise. LiteSpeed affiche un avertissement de conflit ; le test doit donc rester contrôlé et réversible, avec vérification des exclusions privées et désactivation du cache concurrent si nécessaire.

Le contrôle DOM de WP Super Cache confirme : `wp_cache_easy_on=1` est le radio « Mise en cache activée » et `wp_cache_easy_on=0` est actuellement sélectionné. Aucun autre réglage n’a été soumis.

Le mode simple WP Super Cache a été soumis via le formulaire exact (`wp_cache_easy_on=1`, bouton « Mettre à jour l’état »). La vérification de l’état et des performances publiques reste à effectuer.

Après soumission, le radio `wp_cache_easy_on=1` est bien sélectionné. La page propose un bouton séparé « Test du cache » ; aucune option de cache mobile, utilisateurs connectés ou API REST n’a été activée dans cette étape.

Le bouton intégré « Test du cache » a ouvert `about:blank` et n’a fourni aucun résultat exploitable dans la session. La validation sera donc faite par mesures HTTP directes et par les headers/cache files, pas par ce test visuel.

Mesure directe après activation : FR 3,584/3,678 s, EN 3,382/2,791 s, AR 3,425/3,494 s de TTFB. HCDN reste `MISS` sauf une requête EN `HIT`, `server: hcdn`, et aucun header `X-WP-Super-Cache` n’est observé. Le plugin n’apporte donc pas le TTFB `<800 ms` et son conflit avec LiteSpeed n’est pas acceptable pour le staging ; il doit être désactivé et supprimé après ce test.

WP Super Cache a été désactivé avec succès. WordPress affiche désormais les actions « Activer | Supprimer » pour cette extension inactive ; LiteSpeed, Estatik, Polylang, Partikulier Core et le thème restent actifs.

WP Super Cache a été supprimé avec succès. WordPress confirme 4 extensions restantes : Estatik, LiteSpeed Cache, Partikulier Core et Polylang. Le staging est revenu à son état de cache initial, sans changement du thème ou du design.

Le plugin Partikulier Core a été remplacé avec succès par l’archive optimisée SHA-256 `c3d623cdbc960d55399f04b4e4882569cae2441e86d195f8bc4346c76cd8d6b2`. WordPress confirme « L’extension a bien été mise à jour ». L’état actif et le comportement REST doivent encore être vérifiés.

Le bypass REST a été enregistré directement dans `functions.php` du thème actif final30 via CodeMirror et le formulaire WordPress avec nonce valide. L’édition porte uniquement sur le chemin `/partikulier/v1/listings`; aucun style, script, template, texte ou contenu n’a été modifié.

Après enregistrement du bypass sur le thème actif, huit requêtes REST consécutives répondent HTTP 200 avec JSON valide (`data`, `page`), 21 annonces et `page=1`. TTFB : 3,157 / 2,967 / 2,865 / 3,328 / 2,817 / 3,343 / 2,853 / 3,014 s. HCDN est `HIT` sur les huit, mais le seuil `<800 ms` reste largement manqué. Le bypass préserve le contrat sans résoudre le délai réseau/upstream.

Le core a été remplacé une seconde fois par l’archive `partikulier-core-rest-hook-fix-2026-08-26.zip`, SHA-256 `7b1f23e4e33d615da6bf08ced1a16b45f9c6bba3b8c75404f130381f9a3660c6`. Cette version conserve le lazy-load HTML mais charge les classes REST à `rest_api_init`, afin de supporter les appels `rest_do_request()` de la CI froide.

Le troisième déploiement du core est confirmé par WordPress depuis `partikulier-core-cli-compatible-2026-08-26.zip`, SHA-256 `46f4cadc72b378c3eac88448152cf23c060a649d536164ec9b84106e08f4bd73`. Cette version ajoute uniquement la détection `PHP_SAPI === cli` et le hook `rest_api_init`, afin que les contrats PHP CLI puissent charger les classes métier ; elle ne modifie aucun élément de design.
