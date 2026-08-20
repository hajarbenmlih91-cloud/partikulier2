# Version 6.10.0 — quatre points traités

---

## 1. L'upload de photos — bug trouvé et corrigé

**Reproduit** : après sélection de 2 photos, `fichiers dans input : 0`. Rien ne partait.

### La cause

**Deux scripts géraient le même champ et se neutralisaient.**

L'ancien gestionnaire de `main.js` (écrit pour le formulaire d'avant les 3 étapes)
faisait, à chaque sélection :

```js
fileInput.value = "";   // ← vide le champ juste après la sélection
```

Il filtrait aussi sur `jpeg|png|webp|avif` uniquement — les photos d'iPhone en HEIC
étaient rejetées sans message.

### Le correctif

L'ancien gestionnaire ne s'active plus quand le parcours en 3 étapes est présent, et le
nouveau gère désormais tout :

- glisser-déposer
- clic sur une vignette pour retirer une photo
- limite de 15 avec message clair
- **HEIC accepté** (les iPhone envoient parfois un type MIME vide)
- le champ n'est **jamais vidé**

**Vérifié de bout en bout** : annonce créée avec **2 photos** et une miniature.

```
[56] Appartement de 80 m² à vendre à Agdal, Rabat
     statut: pending | photos: 2 | thumb: 57
```

Un point à surveiller : votre serveur limite l'envoi à **2 Mo par fichier**. Les photos
de téléphone dépassent souvent cette taille. Demandez à Hostinger de passer
`upload_max_filesize` à 8 ou 16 Mo — le diagnostic vous le signale désormais.

---

## 2. Le diagnostic par page — votre demande

Nouveau menu : **Partikulier › Diagnostic des pages**

Vous tapez un nom de page, ou cliquez sur un des boutons rapides, et vous obtenez tout.

Exemple réel sur « annonce » :

```
✓  Contenu analysé : « استوديو بمساحة 28 م² للبيع في الرباط »
✓  Meta description : 87 caractères
✓  3 photo(s) rattachée(s)
!  4 image(s) sans texte alternatif
✕  Aucune action « À vendre » / « À louer »
   Pas de badge, et le filtre ne trouvera pas cette annonce
✓  Lieu : El Jadida, Rabat
```

Sur « déposer » :

```
✓  Page trouvée : « Déposer une annonce » (deposer-une-annonce)
✓  Page publiée
✓  Modèle de page correctement assigné
✓  Numéro WhatsApp de validation renseigné
✓  19 types de biens disponibles
✓  Taille maximale d'envoi : 2 Mo
```

Il vérifie selon la page : existence, statut, gabarit assigné, numéro WhatsApp, types de
biens, limite d'upload, meta description et sa longueur, photos et textes alternatifs,
action commerciale, ville rattachée, prix, traductions manquantes.

Un bandeau annonce d'emblée le nombre de problèmes bloquants.

---

## 3. Le message WhatsApp — maintenant personnalisable

**Apparence › Personnaliser › Validation WhatsApp**

Nouveau champ, avec six balises :

| Balise | Contenu |
|---|---|
| `{code}` | Code de validation, ex. `PK-56-BXYE2FE` |
| `{titre}` | Titre de l'annonce |
| `{ville}` | Ville ou quartier |
| `{prix}` | Prix formaté en MAD |
| `{lien}` | URL de l'annonce |
| `{nom}` | Nom de l'annonceur |

Testé avec :

```
Bonjour, je publie {titre} a {ville} au prix de {prix}. Code : {code}. Lien : {lien}
```

Donne, dans WhatsApp :

```
Bonjour, je publie Appartement de 80 m² à vendre à Agdal, Rabat a Rabat
au prix de 850 000 MAD. Code : PK-56-BXYE2FE. Lien : http://…
```

Laissez le champ vide pour revenir au message d'origine.

---

## 4. Le bouton cœur

**Le cœur lui-même fonctionnait** — j'ai vérifié : pas de navigation, favori bien
enregistré. Le vrai problème était ailleurs.

La carte affichait **trois icônes** : un œil « aperçu rapide », le cœur, et une flèche
« voir l'annonce ». Les deux voisines pointent vers l'annonce. Sur une cible aussi
petite — surtout au doigt — vous cliquiez à côté et la page s'ouvrait.

**Votre maquette React n'en montre que deux.** J'ai supprimé la flèche redondante : il
reste l'œil et le cœur, comme prévu. Le lien « Voir l'annonce » existe déjà en bas de
carte et sur l'image.

J'ai aussi ajouté `stopPropagation()` sur le cœur, pour qu'un clic ne remonte jamais à un
lien parent même dans une future mise en page.

---

## Installation

**Apparence › Thèmes › Ajouter › Téléverser** → `partikulier-6.10.0.zip` →
*Remplacer l'actuel*.

Aucune configuration supplémentaire : les quatre correctifs sont actifs immédiatement.

0 erreur PHP, 0 erreur JavaScript.
