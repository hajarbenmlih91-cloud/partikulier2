# Partikulier — documentation du thème

**Version 6.13.0 · août 2026**

> Ce fichier remplace une documentation qui décrivait la version 1.2.0 d'origine et était devenue trompeuse (palette orange, 11 modules, envoi d'e-mail de confirmation — plus rien de tout cela n'est exact).

---

## Ce que fait le thème

Partikulier est un portail immobilier marocain **réservé aux propriétaires particuliers**. Les agences et agents immobiliers sont refusés : c'est le positionnement du produit.

Le parcours complet :

1. Un propriétaire dépose son annonce depuis le site, en 3 étapes, **sans créer de compte**.
2. L'annonce part en attente de validation. Si la ville ou le quartier n'existent pas au référentiel, la proposition part aussi en modération.
3. Un administrateur valide depuis **Partikulier › Valider les annonces**.
4. L'annonce est publiée **dans les trois langues simultanément** (français, arabe, anglais).
5. n8n reçoit l'identifiant et le mot de passe de l'annonceur et les lui envoie sur WhatsApp.

Le thème dépend d'**un seul plugin : Estatik**. Tout le reste est intégré : SEO, cache, conversion AVIF, données structurées, traductions, formulaire, tableaux de bord.

---

## Installation

1. Uploader le zip dans *Apparence › Thèmes › Ajouter*, puis activer. Les pages obligatoires (`deposer-une-annonce`, `mes-annonces`, `favoris`) sont créées automatiquement.
2. Installer et activer **Estatik**.
3. *Réglages › Permaliens › Enregistrer*, sans rien modifier. **Étape obligatoire** : sans elle, les URL d'annonces peuvent renvoyer des 404.
4. *Apparence › Personnaliser › Validation WhatsApp* : renseigner le numéro. **Sans numéro, le formulaire refuse tous les dépôts.**
5. Optionnel : URL du webhook n8n et secret d'automatisation, sur le même écran.

Pour vérifier l'installation : **Partikulier › Diagnostic des pages › Analyser tout le site**.

---

## Les écrans d'administration

| Menu | Rôle |
| --- | --- |
| **Partikulier** | Accueil du thème |
| **Personnalisation du site** | Textes et libellés sans toucher au code |
| **Lieux proposés** | Modération des villes et quartiers proposés par les annonceurs |
| **Valider les annonces** | Publication des dépôts, génération et envoi des identifiants |
| **Mise à niveau** | Assistant de migration pour les sites déjà en production |
| **Diagnostic des pages** | Vérification page par page ou du site entier |
| **Leads WhatsApp** | Demandes acquéreurs qualifiées |

---

## Structure des URL

```
/annonce/casablanca/maarif/appartement-lumineux/     annonce avec quartier
/annonce/rabat/studio-calme/                          annonce sans quartier
```

Les anciennes adresses `/property/…` redirigent en **301**. Une annonce atteinte par un chemin non canonique est également redirigée, ce qui évite qu'elle existe sous plusieurs adresses (contenu dupliqué).

---

## Fonctionnement du SEO

- Accroche déterministe en tête de description, mention « particulier à particulier » en clôture.
- Meta description calibrée entre 140 et 155 caractères.
- `alt` riche sur la photo principale, variantes courtes sur les suivantes (≤ 125 caractères).
- JSON-LD `RealEstateListing`, `ItemList`, `BreadcrumbList`, `WebSite` + `SearchAction`.
- `sitemap.xml` et `robots.txt` générés par le thème.
- **Aucune variation aléatoire** : deux annonces identiques produisent le même texte.

---

## Multilingue

Les versions arabe et anglaise sont composées **par gabarit**, sans API de traduction payante. Le texte libre écrit par l'annonceur est recopié tel quel — une traduction machine approximative ferait plus de mal que de bien. Les trois versions sont publiées ensemble.

Les traductions générées portent la méta `_pk_auto_translation` et sont exclues des listes d'administration.

---

## Performance

- Conversion AVIF automatique à l'envoi. L'original est **conservé** : il sert de repli `<picture>` et permet la régénération des miniatures.
- Cache de page fichier (HTML, Gzip, Brotli) géré par le thème.
- JavaScript vanilla, **zéro jQuery**.
- DM Sans auto-hébergée dans `assets/fonts/`.

---

## Documentation destinée aux développeurs

La documentation technique complète (architecture, décisions de conception, pièges connus, chantiers restants, procédures de test) est fournie séparément dans le **dossier de reprise `dev-partikulier/`**. Elle est indispensable avant toute modification du code : plusieurs choix du thème paraissent être des erreurs quand on ne connaît pas leur raison d'être.

Voir aussi `docs/whatsapp-n8n-setup.md` pour le détail du pont n8n et `docs/leads-admin-guide.md` pour les leads acquéreurs.
