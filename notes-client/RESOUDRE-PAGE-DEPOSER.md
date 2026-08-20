# Résoudre la page « Déposer une annonce » — pas à pas

Deux choses à faire, dans cet ordre. Comptez 5 minutes.

---

# Étape 1 — Installer la v6.4.4

C'est elle qui contient le correctif : le thème crée désormais la page tout seul.

1. **Apparence › Thèmes › Ajouter › Téléverser un thème**
2. Choisissez **`partikulier-6.4.4.zip`**
3. *« Ce thème est déjà installé »* → cliquez sur **Remplacer l'actuel par le téléversé**

> Sur Mac, si Safari a transformé le zip en dossier : clic droit sur le dossier ›
> **Compresser**, et envoyez le zip obtenu.

---

# Étape 2 — Déclencher la création de la page

Le correctif s'active **à l'activation du thème**. Trois façons, choisissez la plus simple.

## Méthode A — L'alerte rouge (la plus simple)

Après l'installation, une alerte apparaît en haut de l'admin :

> **Partikulier — page manquante**
> Ces pages du thème n'existent pas encore : *Déposer une annonce, Mes annonces*.
> [ **Créer les pages manquantes** ]

**Cliquez sur le bouton.** C'est fait.

## Méthode B — Réactiver le thème

*Apparence › Thèmes* → activez un autre thème (ex. Twenty Twenty-Four), puis
**réactivez Partikulier**. Les pages sont créées au passage.

> Votre contenu ne risque rien : changer de thème ne supprime ni annonces ni réglages.

## Méthode C — À la main (si les deux précédentes échouent)

1. **Pages › Ajouter**
2. Titre : **Déposer une annonce**
3. Vérifiez que le permalien est bien **`deposer-une-annonce`** (sous le titre)
4. Dans la colonne de droite, **Attributs de page › Modèle** → choisissez
   **« Déposer une annonce »**
5. **Publier**

Refaites la même chose pour **Mes annonces** (modèle « Mes annonces »).

> Le thème accepte aussi ces slugs sans réglage : `deposer-annonce`, `deposer`,
> `publier-une-annonce`.

---

# Étape 3 — Renseigner le numéro WhatsApp — obligatoire

**Sans cette étape, la page s'affichera mais aucune annonce ne pourra être publiée.**

1. **Apparence › Personnaliser**
2. Panneau **Validation WhatsApp**
3. Champ **« Numéro WhatsApp de validation (format international, sans espaces) »**
4. Saisissez votre numéro, ex. : **`212612345678`**
   - format international, **sans** `+`, **sans** espaces, **sans** `0` initial
   - `06 12 34 56 78` devient `212612345678`
5. **Publier**

---

# Étape 4 — Vérifier

1. Videz le cache de votre navigateur (**Ctrl + F5**, ou **Cmd + Maj + R** sur Mac)
2. Cliquez sur **« Déposer une annonce »** dans le menu du site
3. Le formulaire doit s'afficher, avec **Prix (MAD)**
4. Remplissez-le et validez : vous devez obtenir un **code de validation** du type
   `PK-42-4GYLB6J` et un bouton pour ouvrir WhatsApp

L'annonce arrive alors dans **Annonces** avec le statut **En attente**. Elle devient
visible sur le site une fois que vous la passez en **Publiée**.

---

# Si ça ne marche toujours pas

| Ce que vous voyez | Cause | Solution |
|---|---|---|
| Toujours 404 | Permaliens à régénérer | **Réglages › Permaliens › Enregistrer** (sans rien changer) |
| Page blanche, sans formulaire | Le modèle n'est pas rattaché | Méthode C, étape 4 |
| « Publication impossible : le numéro WhatsApp… » | Étape 3 non faite | Renseignez le numéro |
| « Le dépôt est momentanément indisponible » | Idem, vu par un visiteur | Renseignez le numéro |
| La page existe en français mais pas en anglais/arabe | Polylang | **Pages** → colonnes drapeaux → créez la traduction |

---

# Rappel — l'autre problème, séparé

Le menu **« Achat ou location »** qui affiche des villes est **indépendant** de celui-ci.
C'est un problème de données, pas de code. Il se règle avec `tests/reparer-taxonomies.php`,
après sauvegarde de la base — voir **`ANALYSE-DE-VOTRE-SITE.md`**.

Faites d'abord la page dépôt, puis les taxonomies. Un chantier à la fois.
