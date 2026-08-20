# Page « Déposer une annonce » — parcours en 3 étapes · v6.5.0

Testé sur une pile identique à la vôtre : **WordPress 7.0.4, Estatik 4.3.4, Polylang 3.8.7
(fr / en / ar)**, thème Partikulier actif.

---

## Le test décisif

Votre capture d'écran affiche un aperçu généré automatiquement. J'ai rejoué exactement
les mêmes réponses dans WordPress, dans un vrai navigateur.

**Titre produit par WordPress :**

```
Studio de 72 m² sans vis-à-vis avec terrasse de 20 m² à vendre à Hay Riad, Rabat
```

**Description produite par WordPress :**

```
Studio avec une pièce principale et 2 salles de bains avec terrasse de 20 m² à vendre
à Hay Riad, Rabat, d'une superficie de 72 m², au prix de 124 098 MAD, situé au rdc,
avec garage ou sous-sol, sans ascenseur, sans vis-à-vis, ensoleillé le matin.
```

**Identique à votre maquette React**, mot pour mot.

Une seule différence, volontaire : le prix s'écrit `124 098 MAD` (espace) et non
`124.098 MAD` (point). C'est le format officiel des nombres en français, appliqué par
WordPress. Dites-moi si vous préférez le point, c'est une ligne à changer.

---

## Les 3 étapes

### Étape 1 — Qui publie l'annonce ?

- **Propriétaire / Mandataire** en cartes noires quand sélectionnées
- **Vendre / Louer** au même format
- **Type de bien** — liste alimentée par vos taxonomies Estatik
- **Ville ou quartier de départ** — champ à autocomplétion

### Étape 2 — Les informations du bien

Superficie, chambres, prix, salons, salles de bains, **étage**, **garage ou sous-sol**,
**ascenseur**, sans vis-à-vis, ensoleillement, terrasse, photos.

Les champs Oui/Non sont des **boutons**, noirs à l'état actif, comme sur votre maquette.
La superficie de terrasse n'apparaît que si vous cochez « Oui ».

### Étape 3 — Votre aperçu

Carte de prévisualisation avec vignette photo, badge `VENDRE · PROPRIÉTAIRE`, titre,
description, faits essentiels et prix. En dessous : le **titre modifiable**, un espace
pour un **mot personnel**, puis nom, téléphone et e-mail facultatif.

---

## L'autocomplétion

**30 villes marocaines, environ 280 quartiers**, fusionnés avec vos propres termes
`es_location`.

Testé en conditions réelles, via le navigateur :

| Vous tapez | Suggestions |
|---|---|
| `r` | **Rabat**, Agadir, Berrechid, Essaouira, Ifrane… |
| `cas` | Casablanca |
| `fes` *(sans accent)* | **Fès**, Route de Fès *(Marrakech)* |
| Rabat choisi | Agdal, Hay Riad, Hassan, Souissi, Les Orangers… |
| `agdal` seul | Agdal *(Rabat)*, Agdal *(Marrakech)*, Agdal *(Fès)* |

Les villes qui **commencent** par votre saisie passent toujours en premier. Vous pouvez
aussi choisir un quartier directement : la ville se remplit automatiquement.

Le quartier retenu est **créé dans `es_location`** à la publication, rattaché à sa ville
quand la taxonomie est hiérarchique.

---

## Vérifié de bout en bout

| Test | Résultat |
|---|---|
| Page `/deposer-une-annonce/` | **200** |
| Les 3 étapes présentes | oui, une seule visible à la fois |
| Autocomplétion ville → quartier | fonctionne dans un vrai navigateur |
| Aperçu généré | **identique à la maquette** |
| Dépôt complet | annonce créée, statut `pending` |
| Action rattachée | `A louer` correctement associée |
| Quartier créé | `Gueliz` ajouté à `es_location` |
| Étage / garage / ascenseur | enregistrés |
| **Erreurs JavaScript** | **aucune** |
| Erreurs PHP | **0** sur tout le thème |

**Un bug corrigé au passage** : l'ancien générateur de titre de `main.js` provoquait
6 erreurs JavaScript, car il attendait un menu déroulant pour la ville — devenu un champ
texte. Neutralisé proprement.

**Votre coquille corrigée** : « Choisiaaez l'expoaition » → **« Choisissez l'exposition »**.

---

## Installation

1. **Apparence › Thèmes › Ajouter › Téléverser** → `partikulier-6.5.0.zip` →
   *Remplacer l'actuel par le téléversé*
2. Si l'alerte « page manquante » apparaît, cliquez sur **Créer les pages manquantes**
3. **Apparence › Personnaliser › Validation WhatsApp** → votre numéro, ex. `212612345678`
   — **sans lui, aucune publication ne passe**
4. Ctrl + F5, puis testez le dépôt

---

## Deux points à trancher

**1. Le format du prix** — `124 098 MAD` (actuel, norme française) ou `124.098 MAD`
(votre maquette) ?

**2. La liste des quartiers** — j'ai couvert 30 villes. Si vous ciblez surtout certaines
d'entre elles, envoyez-moi vos quartiers prioritaires et je complète.

---

## Reste en attente

Le menu **« Achat ou location »** de l'accueil affiche toujours des villes. Problème de
données, indépendant de cette page — voir `ANALYSE-DE-VOTRE-SITE.md` et le script
`tests/reparer-taxonomies.php`.
