# Lieux verrouillés + pack SEO · v6.6.0

Testé sur **WordPress 7.0.4, Estatik 4.3.4, Polylang 3.8.7 (fr/en/ar)**, dans un vrai
navigateur.

---

## 1. Les lieux sont verrouillés

Votre règle : *on ne choisit que ce qu'on lui propose ; sinon il propose, et rien n'est
créé sans validation admin.*

### Ce qui a changé

Avant, le formulaire **créait le lieu automatiquement**. Un annonceur pouvait fabriquer
« Casblanca » ou « Rabat centre » et polluer votre taxonomie — exactement le désordre que
vous réparez en ce moment avec `reparer-taxonomies.php`.

Désormais, à la soumission, le thème **cherche** le terme mais ne le crée jamais.

### Le parcours de l'annonceur

1. Il tape sa ville → suggestions
2. Il ne trouve pas → il clique sur **« Je ne trouve pas ma ville ou mon quartier »**
3. Un encadré s'ouvre : Ville (obligatoire) + Quartier (facultatif), avec cet
   avertissement :

   > Attention : votre annonce ne sera mise en ligne qu'après validation de ce lieu par
   > notre équipe. Si vous trouvez votre ville dans la liste, préférez-la : la publication
   > sera immédiate.

4. Choisir un lieu de la liste **annule automatiquement** la proposition — pas de conflit
   possible

### Votre écran de modération

**Partikulier › Lieux proposés**. Une alerte orange apparaît dans l'admin tant qu'une
demande attend.

| Décision | Effet |
|---|---|
| **Créer le lieu et publier** | Le terme est créé dans `es_location`, l'annonce y est rattachée et libérée |
| **Refuser** | **Aucun terme créé**, l'annonce reste hors ligne |

### Résultats des tests

| Test | Résultat |
|---|---|
| Lieu inconnu envoyé sans proposition | **Refusé** — « Choisissez une ville ou un quartier dans la liste proposée » |
| Termes créés dans ce cas | **0** |
| Proposition « Hay Al Qods, Berkane » | Annonce créée, statut `pending`, **bloquée** |
| Terme créé avant validation | **Aucun** |
| Publication forcée pendant l'attente | **Repoussée** en `pending` |
| Après validation admin | Terme `Hay Al Qods` créé, annonce rattachée puis publiée |
| Après refus admin | **Aucun terme**, publication forcée **toujours bloquée** |

**Une fuite trouvée et corrigée pendant les tests** : après un refus, l'annonce pouvait
encore être publiée à la main. Le garde-fou couvrait « en attente » mais pas « refusé ».
Corrigé, revérifié.

---

## 2. L'aperçu reflète bien ce qui sera publié

Oui — les informations saisies **deviennent le texte de l'annonce**. Confirmé au navigateur
avec un lieu proposé :

```
Titre : Appartement de 110 m² à vendre à Hay Al Qods, Berkane
Descr : Appartement avec 2 chambres + salon et 2 salles de bains à vendre à
        Hay Al Qods, Berkane, d'une superficie de 110 m², au prix de 780 000 MAD…
```

L'aperçu se construit au passage à l'étape 3, à partir de **toutes** vos réponses — y
compris un lieu en attente de validation.

---

## 3. Le pack SEO complet

### Texte enrichi

Une **seconde phrase** est ajoutée automatiquement, qui reformule sans se répéter
platement :

> Ce studio est proposé à la vente à Hay Riad, Rabat, en direct avec le propriétaire,
> sans commission d'agence.

Elle place naturellement le type de bien, la transaction, le quartier et la ville — les
requêtes que tapent vos visiteurs.

### Meta description dédiée

Distincte du texte de l'annonce, calibrée pour Google (**87 caractères**, limite 158) :

```
Studio, 72 m² à vendre à Hay Riad, Rabat. 124 098 MAD. Contact direct, sans commission.
```

### Texte alternatif des photos

Chaque image reçoit un `alt` descriptif, précieux pour Google Images :

```
photo 1 : Studio de 72 m² à vendre à Hay Riad, Rabat
photo 3 : Studio de 72 m² à vendre à Hay Riad, Rabat — photo 3
```

### Données structurées

Vérifiées sur une annonce réelle en ligne :

```
JSON-LD : RealEstateListing + BreadcrumbList + WebSite + House
meta    : Appartement, 110 m², 3 chambres + salon ou plus à vendre à Hay Al Qods, Berkane…
H1      : Maison spacieuse avec jardin arboré
```

C'est ce que Google lit pour afficher vos annonces en résultats enrichis.

---

## Installation

1. **Apparence › Thèmes › Ajouter › Téléverser** → `partikulier-6.6.0.zip` →
   *Remplacer l'actuel*
2. Vérifiez le nouveau menu **Partikulier › Lieux proposés**
3. Le numéro WhatsApp reste indispensable :
   *Apparence › Personnaliser › Validation WhatsApp*

---

## Une recommandation

Vos 30 villes et ~280 quartiers couvrent le marché principal. Les propositions vous
diront où la liste est incomplète — c'est un bon indicateur de vos zones de demande
réelle. Regardez cet écran une fois par semaine au début.

**Toujours en attente** : le menu « Achat ou location » de l'accueil affiche des villes.
Problème de données, indépendant — `tests/reparer-taxonomies.php`, après sauvegarde.
