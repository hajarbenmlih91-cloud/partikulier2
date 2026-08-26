
## Vérification publique initiale

L’archive FR `/fr/annonces/` a servi le thème final26 et affiché `21` résultats, avec deux cartes par ligne dans la vue desktop observée. L’URL `/fr/annonces/?es_action=a-vendre` a affiché le sélecteur `Vendre` sélectionné, `21` résultats et des badges `À VENDRE` sur les cartes observées. Cette preuve confirme le comportement nominal du filtre vente sur les données actuellement publiées ; elle ne constitue pas encore un test complet de tous les filtres cumulés ni de la location.

## Smoke multi-navigateurs — premier passage

Le smoke final26 corrigé passe sur Chromium : archives FR/EN/AR, filtre vente, observation location, JSON-LD d’une fiche AR, popup AR mobile et images aux quatre largeurs.

Firefox passe les archives, le filtre vente, le JSON-LD et la popup, mais le premier passage signale 18 images non chargées sur chaque largeur mobile : le harnais scrollait trop vite et ne laissait pas le lazy-load se stabiliser. Ce point doit être rejoué avec une attente bornée après chaque défilement ; il n’est pas déclaré PASS en l’état.

WebKit n’a pas produit de rapport final : il a rencontré une boucle de redirection en ouvrant un slug de fiche AR avec caractères arabes percent-encodés. Cela constitue un résultat de test à traiter dans le harnais ou dans la route, pas un PASS.

## Smoke final26 — résultat corrigé

Après correction des sélecteurs du harnais et ajout d’une attente bornée pour le lazy-load, les trois moteurs passent chacun 7/7 scénarios : archives FR/EN/AR, filtre vente avec 21 cartes dont 20 badges `À VENDRE` (une carte publiée ne porte pas ce badge), observation du filtre location, JSON-LD d’une fiche AR à slug latin stable, popup AR mobile avec `aria-hidden=true` après Échap et retour du focus, puis images valides sans débordement aux largeurs 320/360/375/390.

Le premier échec Firefox provenait de l’attente insuffisante du harnais, et la première erreur WebKit d’un slug arabe percent-encodé qui boucle en redirection. Ces deux résultats ont été conservés comme historique, puis le smoke déterministe a été rejoué ; aucune modification du contenu WordPress n’a été nécessaire.
