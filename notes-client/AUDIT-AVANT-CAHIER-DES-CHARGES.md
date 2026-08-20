# Audit préalable au cahier des charges

Un cahier des charges écrit sans mesure est une liste de suppositions. Voici les tests que
j'ai lancés sur votre thème, les résultats bruts, et ce qu'ils impliquent.

Tout est reproductible : `node tests/audit.mjs` depuis `theme/`.

---

## 1. Sécurité — le plus coûteux à découvrir tard

| Test | Résultat |
|---|---|
| `echo` de `$_GET` / `$_POST` non échappé | **0** (le seul cas passe par `esc_html(sanitize_text_field(wp_unslash()))`) |
| `echo` de variables sans échappement | 3 occurrences, **toutes sûres** après vérification : valeur déjà échappée en amont, booléen, ou SVG codé en dur |
| Requêtes SQL sans `prepare()` | 18 détectées, **0 vulnérable** — ce sont des `START TRANSACTION` / `COMMIT` / `ROLLBACK` sans données |
| Handlers AJAX / admin-post | 12, avec 9 vérifications de nonce |
| Contrôles de capacités | 12 `current_user_can`, 10 `is_user_logged_in` |

**Conclusion** : aucune faille exploitable trouvée. Le rapport 12 handlers / 9 nonces mérite
une revue ciblée — certains handlers publics n'en ont pas besoin, mais il faut le confirmer
un par un.

---

## 2. Un bug bloquant découvert par l'audit

**Polylang masque la totalité des annonces sur la page d'accueil.**

Mesure A/B, même site, même contenu :

| | Cartes affichées |
|---|---|
| Polylang désactivé | **6** |
| Polylang activé | **0** |

Cause : les annonces n'ont aucune langue assignée (`pll_get_post_language()` renvoie
`false`), et le type `properties` n'est pas déclaré comme traduisible dans Polylang. Ce
dernier filtre alors tout contenu sans langue.

C'est le genre de défaut qu'un cahier des charges écrit sans mesure ne mentionnerait pas —
et qui rendrait le site vide en production.

**À vérifier chez vous en priorité** : si votre accueil affiche « Aucune annonce publiée
pour le moment » alors que vous en avez, c'est exactement ce problème. Deux corrections
possibles : déclarer `properties` traduisible dans *Langues › Réglages*, ou assigner une
langue par défaut aux annonces existantes.

---

## 3. Performance — où part le poids

Page d'accueil, 1440 px, réseau local :

| Source | Requêtes | Poids |
|---|---|---|
| **Plugin Estatik** | 12 | **483 Ko** |
| Thème Partikulier | 4 | 338 Ko |
| Cœur WordPress | 6 | 157 Ko |
| **Total** | **26** | **977 Ko** |

Les plus gros scripts : jQuery 86 Ko, Select2 77 Ko, datetimepicker 60 Ko, Slick 42 Ko.

### Mesure A/B : retirer les assets Estatik du front

| | Requêtes | Poids | Premier rendu |
|---|---|---|---|
| Avec Estatik | 26 | 977 Ko | 292 ms |
| **Sans** | **5** | **337 Ko** | **176 ms** |

**−65 % de poids, −40 % sur le premier rendu.**

**Réserve mesurée** : la page s'allonge de 1151 px sans les CSS Estatik. Le retrait n'est
donc pas gratuit — certaines mises en page en dépendent. Le chantier consiste à retirer
**sélectivement** ce qui ne sert pas sur chaque page, pas à tout supprimer.

---

## 4. Accessibilité

| Contrôle | Accueil | Annonces | Déposer |
|---|---|---|---|
| Images sans `alt` | 0 | 0 | 0 |
| Liens sans intitulé | 0 | 0 | 0 |
| Champs sans label | 0 | 0 | 0 |
| Boutons sans intitulé | 0 | 0 | 0 |
| `<h1>` par page | 1 | 1 | 1 |
| Sauts de niveau de titre | 0 | 0 | 0 |
| `lang` sur `<html>` | `fr-FR` | `fr-FR` | `fr-FR` |
| Lien d'évitement | oui | oui | oui |

**Un seul défaut** : **11 à 12 zones cliquables** par page mesurent moins de 24 × 24 px
(critère WCAG 2.2 « Target Size Minimum »). À corriger — c'est peu coûteux et cela devient
une obligation légale en Europe.

---

## 5. SEO et données structurées

| Page | Titre | Description | JSON-LD | `og:image` |
|---|---|---|---|---|
| Accueil | 11 car. | 87 car. | WebSite + RealEstateAgent | **absent** |
| Annonces | 23 car. | 114 car. | ItemList + BreadcrumbList + WebSite | **absent** |
| Déposer | 33 car. | 106 car. | WebSite + BreadcrumbList | **absent** |

- JSON-LD **valide** sur les trois pages, `canonical` présent, 7 balises `hreflang`.
- **`og:image` manquant partout** : vos partages sur WhatsApp, Facebook et LinkedIn
  s'afficheront sans visuel. Correction rapide, impact commercial direct.
- Le titre de l'accueil (11 caractères) est très court pour le référencement.

---

## 6. Qualité du code

| Indicateur | Valeur |
|---|---|
| Fichiers PHP | 51 (8 791 lignes) |
| Erreurs de syntaxe PHP | 0 |
| Erreurs JavaScript en console | 0 |
| Règles CSS | 635 (72 Ko) |
| Sélecteurs dupliqués | 0 |
| `!important` | 1 (styles d'impression, légitime) |
| Textes en dur non traduisibles | 0 |
| Nœuds DOM par page | 358 à 434 |
| Tests de non-régression | 12 vues, 8 invariants |

---

## Ce que ces mesures impliquent pour le cahier des charges

Par ordre de priorité, fondé sur les chiffres ci-dessus :

1. **Corriger l'intégration Polylang** — bloquant. Un site qui n'affiche aucune annonce n'a
   pas d'autre priorité.
2. **Ajouter `og:image`** — quelques lignes, effet immédiat sur les partages.
3. **Alléger Estatik page par page** — jusqu'à −65 % de poids, avec la réserve de mise en
   page mesurée plus haut.
4. **Agrandir les zones cliquables** à 24 × 24 px minimum — conformité WCAG 2.2.
5. **Revoir les 3 handlers sans nonce** — vérification, pas forcément correction.

Ce que je **ne** mettrais pas dans le cahier des charges, faute de justification mesurée :
refonte CSS (déjà à 0 doublon), réécriture en blocs FSE, ajout d'un framework.

---

## Ce qui n'a pas été mesuré, et pourquoi

Par honnêteté, voici les angles morts de cet audit :

- **Lighthouse / Core Web Vitals réels** : mesurés en local, sans latence réseau ni CPU
  contraint. Les chiffres de production seront moins bons. À refaire sur votre hébergement.
- **Charge et montée en volume** : testé avec 6 annonces. Le comportement à 5 000 annonces
  est inconnu — c'est là que se révèlent les requêtes non indexées.
- **Compatibilité navigateurs** : tout est mesuré sous Chromium. Safari et Firefox n'ont pas
  été testés, notamment pour `:has()` que le thème utilise.
- **Parcours utilisateur complet** : le dépôt d'une annonce de bout en bout, avec envoi de
  photos et vérification WhatsApp, n'a pas été exécuté.
- **Sécurité applicative approfondie** : pas de test d'intrusion, pas d'analyse des droits
  entre utilisateurs (un propriétaire peut-il modifier l'annonce d'un autre ?).

Ces cinq points sont eux-mêmes des lignes du cahier des charges.
