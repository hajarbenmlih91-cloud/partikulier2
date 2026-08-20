# Changelog

Le thème est parti d'une version 5.5.2 livrée par un prestataire précédent. Un audit a précédé la reprise ; les versions 6.x sont le travail de refonte.

---

## 6.14.1 — Correctifs techniques sans modification du design

- Archive Estatik francisée sur `/annonces/`, avec redirections 301 de `/property/` et de sa pagination.
- URLs de templates, sitemap, canonical et hreflang alignées sur l’archive publique.
- JSON-LD corrigé en devise `MAD` et pays `MA`.
- Nettoyage conditionnel des assets Estatik lourds hors des parcours immobiliers nécessaires.
- Normalisation défensive des meta descriptions pour supprimer les séparateurs doubles issus des champs Polylang vides.
- Contrôle E5 corrigé : le script compte le véritable listener favori et non les mentions de classe.
- Version alignée sur 6.14.1 dans le thème et les manifests.

## 6.13.0 — URL géographiques et mot de passe WhatsApp

- URL des annonces incluant ville et quartier : `/annonce/casablanca/maarif/mon-bien/` au lieu de `/property/mon-bien/`.
- Redirection 301 de toutes les anciennes adresses ; aucune position SEO perdue.
- Canonisation : une annonce atteinte par un chemin non canonique est redirigée, y compris avec une ville erronée. Évite le contenu dupliqué.
- Sitemap, balise canonical et JSON-LD alignés sur la nouvelle adresse.
- Translittération des accents et apostrophes (`Tétouan` → `tetouan`, `M'Hamid` → `m-hamid`).
- Ville et quartier figés en métas : renommer un terme ne casse pas les URL indexées.
- **Changement de politique** : l'annonceur reçoit son mot de passe en clair sur WhatsApp, à la place du lien à usage unique de la 6.12.0.
- Mot de passe sans caractères ambigus (ni `O`/`0`, ni `I`/`l`/`1`).
- Une revalidation ne réinitialise plus le mot de passe d'un annonceur déjà actif.
- Bouton « Nouveau mot de passe » pour un annonceur ayant perdu son message.
- Nouveau module `class-listing-urls.php`.

## 6.12.0 — Validation des dépôts et pont n8n

- Écran d'administration « Valider les annonces » avec pastille de compteur.
- Publication en un clic : les trois langues partent ensemble, le cache est purgé.
- Garde-fou : publication bloquée tant que le lieu proposé n'est pas validé.
- Webhook sortant vers n8n à la validation, authentifié par en-tête.
- Route de rattrapage `GET /wp-json/partikulier/v1/approved-listings` (72 h, drapeau `webhook_sent`).
- Contournement de `wp_http_validate_url()` pour les n8n auto-hébergés.
- Correction du cœur des favoris : **deux gestionnaires de clic** s'annulaient mutuellement ; un seul subsiste.
- Style `.pk-wish-active` (rouge `#e0245e` + pulsation), état restauré au chargement, `aria-pressed`.
- Nouveau module `class-listing-approval.php`.
- *(Remplacé en 6.13.0 : lien de définition de mot de passe à usage unique, 48 h, jeton haché.)*

## 6.11.0 — Favoris et diagnostic global

- Page Favoris réelle (`templates/page-favoris.php`), déclarée comme page obligatoire.
- Bouton « Analyser tout le site » : 6 pages vérifiées en un clic, synthèse des problèmes bloquants et avertissements.

## 6.10.x — Corrections d'exploitation

- **Upload de photos réparé** : `main.js` réinitialisait le champ fichier en doublon de `submit-steps.js`.
- Diagnostic page par page (`class-page-doctor`).
- Numéro WhatsApp personnalisable.
- Détection réelle de la capacité HEIC (`Imagick::queryFormats()`), au lieu de se fier à `get_allowed_mime_types()`.

## 6.9.0 — Assistant de mise à niveau

- `class-upgrade-wizard` : migration guidée des sites déjà en production.

## 6.8.x — Annonces multilingues

- Génération FR / AR / EN par gabarit, sans API payante.
- Le texte libre de l'annonceur est recopié tel quel.
- Publication simultanée des trois versions.
- Rattrapage des annonces existantes (6.8.1).

## 6.7.0 — Suppression du rôle mandataire

- Rôle et vocabulaire retirés du code, de l'interface et de la base.
- Le formulaire propose « Propriétaire » ou « Agent immobilier », ce dernier étant refusé.

## 6.6.x — Lieux verrouillés et pack SEO

- Référentiel des villes et quartiers marocains (`class-morocco-places`).
- Modération des lieux proposés (`class-place-requests`) : aucune création automatique.
- Accroche déterministe en tête de description, mention « particulier à particulier » en clôture.
- Meta description calibrée entre 140 et 155 caractères.
- `alt` riche sur la photo principale, variantes courtes ensuite.
- JSON-LD `RealEstateListing`.

## 6.5.0 — Parcours de dépôt en 3 étapes

- Refonte du formulaire, autocomplétion des lieux, `submit-steps.js`.

## 6.4.x et antérieures — Assainissement

- 0 doublon CSS, suppression des `!important` (12 vues vérifiées).
- Correction des taxonomies mal classées (villes rangées dans `es_category`).
- Statut HTTP correct sur les refus du formulaire.
- Suppression de 49 classes CSS mortes.
- Passage à la charte cognac `#9b6a3d`, abandon de l'orange `#f85525`.
- DM Sans auto-hébergée.

---

## Note sur l'historique git

**L'historique du dépôt n'est pas fiable et ne doit pas servir de référence.**

Le projet a été développé dans un environnement dont les snapshots excluent une partie de `.git`. Résultat : les commits sont régulièrement perdus et le `HEAD` retombe sur un état ancien (v6.5.0), alors que les fichiers de travail sont bien à jour. Le phénomène s'est produit plusieurs fois.

Ce qui fait foi, dans l'ordre :

1. **L'archive zip livrée** — c'est la référence.
2. **Les fichiers de `theme/`** — `PARTIKULIER_VERSION` dans `functions.php` donne la version réelle.
3. Le `readme.txt` et son changelog.

L'historique git, lui, peut afficher n'importe quel état antérieur. Si tu reprends ce projet, **initialise un dépôt neuf** depuis le contenu du zip et pousse-le sur un hébergeur distant (GitHub, GitLab) dès la première heure. Les versions listées ci-dessus sont reconstituées à partir du `readme.txt` et des livraisons successives, pas du journal git.

## 6.14.1 — recette réelle complémentaire

Recette sandbox exécutée avec Estatik 4.3.4, Polylang 3.8.7 et Query Monitor 4.0.7. Les langues FR/EN/AR, les familles de traductions, les invariants de taxonomies, les assets Estatik, la galerie et les favoris ont été testés réellement. Le rapport documente également la comparaison historique 6.13.1 et précise que le lot B ne peut pas être déclaré comme gain N+1 démontré tant que le protocole avant/après n’est pas strictement homogène.
