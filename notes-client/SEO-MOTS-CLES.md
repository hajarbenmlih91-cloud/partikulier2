# SEO — accroche, meta description, alt · v6.6.1

Vos trois questions, avec les chiffres vérifiés et mon avis de professionnel.

---

## 1. « Propriétaire vend » — bonne idée, avec une correction

Votre intuition est juste : *propriétaire vend* et *particulier vend* sont des requêtes à
forte intention. Celui qui les tape cherche à éviter les agences. C'est votre
positionnement exact.

### Ce que je n'ai pas suivi

Vous proposiez d'alterner « parfois particulier vend ». **Une variation aléatoire est une
mauvaise pratique** : vos pages deviennent imprévisibles, vous ne pouvez plus mesurer ce
qui fonctionne, et Google voit un site incohérent.

J'ai retenu une règle **déterministe**, liée à une réalité vérifiable :

| Rôle déclaré | Vendre | Louer |
|---|---|---|
| Propriétaire | **Propriétaire vend** | **Propriétaire loue** |
| Mandataire | **Mandataire vend** | **Mandataire loue** |

### Et « particulier » ?

Capté dans la **phrase de clôture**, sans jamais mentir :

> Bien de particulier à particulier proposé à la vente à Hay Riad, Rabat, en contact
> direct avec le propriétaire, sans commission ni intermédiaire.

Vous couvrez les deux familles de mots-clés sur la même page.

### Résultat

```
Propriétaire vend studio de 72 m² à Hay Riad, Rabat. Studio avec une pièce principale
et 2 salles de bains avec terrasse de 20 m² à vendre à Hay Riad, Rabat, d'une superficie
de 72 m², au prix de 124 098 MAD, situé au rdc, avec garage ou sous-sol, sans ascenseur,
sans vis-à-vis, ensoleillé le matin. Bien de particulier à particulier proposé à la
vente à Hay Riad, Rabat, en contact direct avec le propriétaire, sans commission ni
intermédiaire.
```

```
Mandataire loue appartement de 120 m² à Maarif, Casablanca. […]
```

**Le titre H1 reste inchangé** — volontairement. « Studio de 72 m² sans vis-à-vis… »
correspond à ce que les gens cherchent vraiment. Mettre « Propriétaire vend » dans le
titre l'aurait alourdi sans gain.

---

## 2. Meta description : votre question était la bonne

**Non, mes 87 caractères ne respectaient pas l'optimum.** Ils ne dépassaient rien, mais
gaspillaient plus de 60 caractères de visibilité gratuite.

### Les limites réelles (vérifiées)

Google tronque en **pixels**, pas en caractères.

| Support | Pixels | Caractères |
|---|---|---|
| Desktop | ~920 px | **150-160** |
| Mobile | ~680 px | **110-120** |

Sources : plusieurs guides SEO 2026 concordants. La règle qui compte : **placer
l'essentiel dans les 120 premiers caractères**, car le mobile coupe là.

### Ce que produit le thème maintenant

```
Propriétaire vend studio 72 m² à Agadir. 124 098 MAD. Terrasse, sans vis-à-vis,
garage. Contact direct, sans commission.
```
**120 caractères** — visible en entier sur mobile **et** desktop.

```
Mandataire loue appartement 120 m², 3 chambres + salon ou plus à Maarif, Casablanca.
9 500 MAD. Garage, ascenseur. Contact direct, sans commission.
```
**147 caractères** — plein format desktop, mots-clés dans les 120 premiers.

### La logique

1. **Bloc prioritaire** (mobile) : qui + action + type + surface + lieu + prix
2. **Compléments** ajoutés seulement s'il reste de la place : terrasse, vis-à-vis,
   garage, ascenseur
3. **Argument de conversion** en dernier, avec repli court si nécessaire
4. **Coupe au dernier mot entier** — jamais en plein milieu

---

## 3. Alt des photos : oui, mais pas partout pareil

**Votre idée de tout mettre partout serait contre-productive.** 15 photos portant le même
texte, c'est le signal classique du keyword stuffing — Google dévalue.

### La bonne approche

**Photo principale** — la plus riche, c'est elle que Google Images associe à l'annonce :

```
Studio de 72 m² à vendre à Hay Riad, Rabat — annonce de propriétaire, sans commission
```

**Photos suivantes** — angles et caractéristiques qui tournent :

```
photo 2 : Studio à Hay Riad, Rabat — vue intérieure, terrasse de 20 m² (photo 2)
photo 3 : Studio à Hay Riad, Rabat — pièce de vie, sans vis-à-vis (photo 3)
photo 4 : Studio à Hay Riad, Rabat — espace intérieur, ensoleillé le matin (photo 4)
photo 5 : Studio à Hay Riad, Rabat — vue depuis le séjour, au rez-de-chaussée (photo 5)
photo 6 : Studio à Hay Riad, Rabat — détail du logement, terrasse de 20 m² (photo 6)
```

Chaque image est **unique**, chacune ancre « type + lieu », et chacune ajoute une
caractéristique différente. Vous couvrez plus de requêtes qu'avec une répétition, sans
risque.

Tous les alt sont plafonnés à **125 caractères** : au-delà, les lecteurs d'écran les
tronquent et Google les juge suspects.

---

## Vérifié

| Contrôle | Résultat |
|---|---|
| Meta en production (page réelle) | **120 caractères**, desktop OK, mobile OK |
| Accroche propriétaire | « Propriétaire vend studio de 72 m²… » |
| Accroche mandataire | « Mandataire loue appartement de 120 m²… » |
| Alt photo principale | 85 caractères, complet |
| Alt photos 2 à 6 | tous différents, 65 à 77 caractères |
| Erreurs PHP | **0** |

---

## Installation

**Apparence › Thèmes › Ajouter › Téléverser** → `partikulier-6.6.1.zip` →
*Remplacer l'actuel*.

Les annonces **déjà publiées gardent leur ancien texte** : ces règles s'appliquent aux
nouveaux dépôts. Si vous voulez régénérer les anciennes, je peux écrire un script — dites-le.

---

## Un conseil pour la suite

Ces formules sont bonnes, mais la vraie mesure viendra de la **Search Console**. Dans deux
ou trois mois, regardez quelles requêtes rapportent des clics : si « particulier vend
appartement Casablanca » ressort, on renforcera cette formulation. On optimisera alors sur
des données réelles, pas sur des hypothèses.
