
## Vérification publique initiale

L’archive FR `/fr/annonces/` a servi le thème final26 et affiché `21` résultats, avec deux cartes par ligne dans la vue desktop observée. L’URL `/fr/annonces/?es_action=a-vendre` a affiché le sélecteur `Vendre` sélectionné, `21` résultats et des badges `À VENDRE` sur les cartes observées. Cette preuve confirme le comportement nominal du filtre vente sur les données actuellement publiées ; elle ne constitue pas encore un test complet de tous les filtres cumulés ni de la location.

## Smoke multi-navigateurs — premier passage

Le smoke final26 corrigé passe sur Chromium : archives FR/EN/AR, filtre vente, observation location, JSON-LD d’une fiche AR, popup AR mobile et images aux quatre largeurs.

Firefox passe les archives, le filtre vente, le JSON-LD et la popup, mais le premier passage signale 18 images non chargées sur chaque largeur mobile : le harnais scrollait trop vite et ne laissait pas le lazy-load se stabiliser. Ce point doit être rejoué avec une attente bornée après chaque défilement ; il n’est pas déclaré PASS en l’état.

WebKit n’a pas produit de rapport final : il a rencontré une boucle de redirection en ouvrant un slug de fiche AR avec caractères arabes percent-encodés. Cela constitue un résultat de test à traiter dans le harnais ou dans la route, pas un PASS.

## Smoke final26 — résultat corrigé

Après correction des sélecteurs du harnais et ajout d’une attente bornée pour le lazy-load, les trois moteurs passent chacun 7/7 scénarios : archives FR/EN/AR, filtre vente avec 21 cartes dont 20 badges `À VENDRE` (une carte publiée ne porte pas ce badge), observation du filtre location, JSON-LD d’une fiche AR à slug latin stable, popup AR mobile avec `aria-hidden=true` après Échap et retour du focus, puis images valides sans débordement aux largeurs 320/360/375/390.

Le premier échec Firefox provenait de l’attente insuffisante du harnais, et la première erreur WebKit d’un slug arabe percent-encodé qui boucle en redirection. Ces deux résultats ont été conservés comme historique, puis le smoke déterministe a été rejoué ; aucune modification du contenu WordPress n’a été nécessaire.

## CI GitHub final26

Le commit `4a12222887e8be91a72a033762c5df70e998d1e1` a été poussé sur `automation/release-approval-gate-v1.7.1`. Le workflow `cdc-v1.7.1-candidate` a démarré avec les runs `32922569488` et `32922573319`.

À la dernière observation, les gates statiques, contrat visuel, dépendances/SBOM, Semgrep et secret scan sont PASS. Le job d’acceptation froide MariaDB/WordPress est encore `in_progress` sur l’étape `Run fresh environment and all candidate acceptance suites`, sans conclusion disponible. CI global : **NOT PASS / EN COURS**.

## Axe et JSON-LD AR individuels

Le gate axe final26 régénéré avec le SHA courant `a4838531c6bdbbbebda6d564e92d632c22af67b2` passe 6/6 sur FR/EN/AR, desktop/mobile : 0 violation axe, 0 bouton visible sans libellé, 0 champ visible sans libellé et 0 image sans alt. La limitation reste la revue WCAG humaine.

Le contrôle dédié des trois premières fiches AR publiques passe 3/3 en Chromium : HTTP 200, `lang=ar`, `dir=rtl`, JSON-LD présent avec nom arabe et aucun `Saïdia`/`Annonces immobilières gratuites` dans le graphe contrôlé.

## Semgrep final26

Après le correctif du retour `false` d’`openssl_decrypt`, le scan ciblé final26 sur 8 fichiers métier passe à **0 finding** (`targeted_exit=0`). Le scan projet complet avec le ruleset `p/default` couvre 357 fichiers et remonte **49 findings bloquants** (`project_exit=1`), principalement dans des surfaces historiques hors périmètre corrigé. Le résultat global Semgrep reste donc **FAIL / NON VALIDÉ** ; il n’est pas masqué par le scan ciblé.
