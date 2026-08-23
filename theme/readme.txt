=== Partikulier ===

Contributors: partikulier
Tags: real-estate, property, listings, immobilier, performance, avif
Requires at least: 6.0
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 6.17.17
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Portail immobilier de particuliers. Annonces gratuites, ultra-rapide, SEO et LLM-ready.

== Description ==

Partikulier est un theme WordPress dedie aux portails immobiliers entre particuliers.
Il est concu pour fonctionner avec un seul plugin : **Estatik** (gratuit, sur wordpress.org).

Caracteristiques :

* Deposez d'annonce gratuite en 2 minutes, sans inscription prealable
* Conversion automatique de toutes les images uploadees en AVIF (jusqu'a -50 % de poids)
* Cache de pages integre au theme (HTML + Gzip + Brotli) — pas besoin de plugin de cache
* Schema.org JSON-LD complet (RealEstateListing, ItemList, WebSite, SearchAction, BreadcrumbList, RealEstateAgent)
* Sitemap.xml et robots.txt generes automatiquement, sans plugin SEO
* SEO geographique : pages villes/regions auto-generees, fil d'Ariane, URLs propres
* Contenu lisible par les LLM (structure semantique, donnees structurees, pas de JS bloquant)
* Zero jQuery : ~3 ko de JavaScript vanilla
* Optimisations PageSpeed : preload, preconnect, lazy-loading natif, compression HTML
* Design moderne inspire des kits premium : degrade violet/orange, vague SVG, cartes epurees

== Installation ==

1. Installez et activez le plugin **Estatik** (un seul plugin requis).
2. Uploadez le theme dans `wp-content/themes/partikulier/` ou via Apparence > Themes > Ajouter.
3. Activez le theme Partikulier.
4. Reglages > Permaliens : choisissez "Nom de la publication" puis Sauvegarder.
5. Creez la page **Deposer une annonce** (slug `deposer`) avec le template "Deposer une annonce".
6. Reglages > Lecture : page d'accueil = "Vos dernieres annonces" (laissez la page d'accueil a vide pour que le theme utilise front-page.php).
7. Apparence > Menus : creez le menu "Menu principal" (Accueil, Annonces, Deposer une annonce, Mentions legales).

== Configuration ESTATIK ==

* Estatik > Settings : activez les types (appartement, maison, terrain...) et les actions (vendre, louer).
* Creez au moins une ville par annonce (Estatik > Addresses ou via le formulaire du theme).
* Le theme surcharge automatiquement les templates d'Estatik via le dossier `estatik4/front/`.

== Changelog ==

= 6.17.17 =
* CDC v1.7.1 candidate : core M0, contrats d’intégration, preuves CI sidecar, package déterministe et baselines visuelles viewport-only.
* Les tests de qualification et la publication restent conditionnés aux gates CI et aux approbations requises.

= 6.17.16 =
* Revue senior : migration idempotente de l’ancien slug `deposer-une-annonce` vers `/deposer/` et exclusion des espaces privés du cache public.
* Reproductibilité renforcée : sorties JSON strictes, suivi des chaînes de redirection, WP-CLI vérifié par SHA-512, `npm ci` et contrôle Node 22.

= 6.17.10 =
* Livraison CDC fermée autosuffisante : scripts, contrat de routes, 30 baselines visuelles versionnées, preuves HMAC/SQL/Semgrep et recette froide.
* Polylang FR/EN/AR : routes préfixées, famille de traductions publiée, détection navigateur et direction RTL vérifiées.
* Cache : les réponses HTML vides ne sont plus persistées comme pages publiques.
* Compatibilité déclarée : PHP 8.0+ et WordPress 7.1 testé.

= 6.17.7 =
* Fix: Senior CDC v1.5 compliance (N+1, RTL, slugs, pagination, HMAC proofs)
* Fix: PHP 8.4 runtime support
* Fix: N+1 SQL optimization (106 -> 91 queries)
* Fix: Clean translated slugs (removed "إعلان مترجم:")
* Fix: Arabic meta descriptions localization
* Fix: RTL visual invariants in header and menu

= 6.17.7 =
* i18n trilingue : détection Polylang du navigateur, cookie prioritaire et exemption Googlebot/Bingbot.
* Traductions gettext `ar.mo` et `en_US.mo` prioritaires sur les dictionnaires internes, avec police Noto Sans Arabic locale et RTL mobile.
* Parcours de dépôt et champs libres annotés par langue, slugs AR et contrôles SEO `lang`, JSON-LD, Open Graph et hreflang.
* Racine protégée contre le cache partagé avec `private, no-store` ; URLs localisées conservées dans le cache public sous contrôle de langue.
* Hardening R6 : Scanner de littéraux renforcé (guillemets simples/doubles) et détection exhaustive des chaînes UI non traduites.
* Performance Senior : Optimisation SQL (prévention N+1 via priming cache meta/terms), passage de 180+ à ~30 requêtes sur l'archive.
* UX & Pagination : Correction du blocage à 40 annonces, support illimité et grilles responsive optimisées (24 annonces/page).
* Recettes AR/EN/FR, robots, cache chaud/froid, police, chaînes non traduites et non-régression visuelle prévues par le CDC 6.17.

= 6.16.0 =
* Sécurité n8n : secret dédié, migration reprenable hors `pk_theme_options`, HMAC SHA-256 canonique, timestamp anti-rejeu et rotation à double clé.
* Routes d’automatisation protégées par un wrapper REST commun, avec refus fail-closed des secrets absents ou invalides.
* Journalisation idempotente des événements avec préfixes `n8n-`/`pay-`, gestion des collisions UNIQUE et quota configurable `quota_per_day` sous transaction.
* Sept recettes JSON exécutées sur sandbox fraîche, plus non-régression H/K et Polylang ; aucun warning PHP 8.4 capturé.

= 6.15.0 =
* Lot H : textes éditoriaux FR/EN/AR avec fallback FR, validation d’alt hero et purge de cache sur création et mise à jour des options.
* Lot K : tableau Leads, filtres, KPI, export CSV protégé, statuts de suivi et route de renvoi d’identifiants corrélée par UUID.
* Recette dynamique : contrôles PHP, H/K, budget SQL à 1/20/100 lignes et neutralisation CSV injection.

= 6.13.1 =
* Documentation de reprise complete dans docs/reprise/ : architecture, decisions et leurs justifications, changelog, pieges connus, chantiers restants, procedures de test.
* DOC.md reecrit : l'ancienne version decrivait encore le theme 1.2.0 d'origine (palette orange, 11 modules, e-mail de confirmation) et induisait en erreur.
* guide-utilisation.md ne duplique plus DOC.md : il y renvoie, pour eviter que deux versions divergent.
* docs/whatsapp-n8n-setup.md complete : envoi des identifiants a la validation, role du champ send_credentials, route de rattrapage.

= 6.13.0 =
* URL des annonces incluant la ville et le quartier : /annonce/casablanca/maarif/mon-bien/ au lieu de /property/mon-bien/.
* Toutes les anciennes adresses sont redirigees en 301 : aucune position acquise n'est perdue.
* Une annonce accessible par plusieurs chemins est ramenee a son URL canonique, pour ne pas diviser le signal SEO.
* Sitemap, balise canonical et JSON-LD utilisent la nouvelle adresse.
* Identifiants : l'annonceur recoit desormais son mot de passe sur WhatsApp, consultable a tout moment dans sa messagerie, au lieu d'un lien a usage unique.
* Le mot de passe evite les caracteres ambigus (O/0, I/l/1) pour etre recopie sans erreur depuis un telephone.
* Une revalidation d'annonce ne reinitialise plus le mot de passe d'un annonceur deja actif.
* Nouveau bouton « Nouveau mot de passe » pour un annonceur ayant perdu son message WhatsApp.

= 6.12.0 =
* Favoris : le coeur devient rouge au clic, reste rouge apres rechargement, et redevient vide au retrait. Suppression d'un second gestionnaire de clic qui annulait le premier.
* Nouvel ecran d'administration « Valider les annonces » : publication en un clic, avec le nom, le telephone et le code WhatsApp de l'annonceur.
* A la validation, n8n recoit le nom d'utilisateur et un lien securise de definition du mot de passe (48 h, usage unique). Aucun mot de passe en clair n'est transmis ni stocke.
* Route de rattrapage GET /wp-json/partikulier/v1/approved-listings pour les validations des 72 dernieres heures si le webhook a echoue.

= 6.11.0 =
* Nouveau : page Favoris reelle (le coeur du header menait a l'archive)
* Nouveau : diagnostic de tout le site en un clic

= 6.10.1 =
* Versions alignees entre style.css, package.json et readme.txt
* Photos refusees : message explicite au lieu d'un echec silencieux
* HEIC propose uniquement si le serveur sait le convertir

= 6.10.0 =
* Correction : l'upload de photos etait annule par un ancien gestionnaire JavaScript
* Nouveau : ecran Partikulier > Diagnostic des pages (controle page par page)
* Nouveau : message WhatsApp personnalisable avec variables {code} {titre} {ville} {prix} {lien} {nom}
* Correction : bouton favoris — suppression de l'icone redondante qui captait le clic

= 6.9.0 =
* Nouveau : assistant Partikulier > Mise a niveau, trois etapes verrouillees dans l'ordre

= 6.8.0 =
* Nouveau : chaque annonce existe en francais, anglais et arabe, pages liees par hreflang
* Correction : les URL traduites renvoyaient une 404 (regles de reecriture)
* Correction : balises hreflang en double entre Polylang et le theme

= 6.7.0 =
* Le site est reserve aux proprietaires : les agents immobiliers sont refuses
* Suppression du role "mandataire"

= 6.6.0 =
* Lieux verrouilles : plus aucune ville creee sans validation administrateur
* SEO : meta description calibree, textes alternatifs varies, texte enrichi

= 6.5.0 =
* Parcours de depot en 3 etapes avec apercu automatique de l'annonce
* Autocompletion ville puis quartier (30 villes, environ 280 quartiers)

= 6.4.0 =
* Creation automatique des pages "Deposer une annonce" et "Mes annonces"
* Prix affiches en MAD

= 1.3.0 =
* Panneau "Partikulier — Textes du site" dans Apparence > Personnaliser
* Purge automatique du cache apres modification des textes

= 1.2.0 =
* Premiere version
