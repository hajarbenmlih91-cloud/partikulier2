# Annonces en 3 langues · v6.8.0

**Oui, c'est fait.** Une annonce déposée en français devient trois pages distinctes —
française, anglaise, arabe — chacune avec sa propre URL, son titre, sa description et sa
meta description, toutes liées entre elles pour Google.

Testé sur WordPress 7.0.4, Estatik 4.3.4, Polylang 3.8.7 (fr/en/ar).

---

## Trois URL réelles

```
FR  /property/appartement-de-60-m²-a-louer-a-agadir/
EN  /en/property/apartment-of-60-sqm-for-rent-in-agadir/
AR  /ar/property/شقة-بمساحة-60-م²-للكراء-في-أكادير/
```

Le slug lui-même est traduit — un signal fort pour le référencement local.

---

## Comment la traduction fonctionne

Le texte d'une annonce n'est pas de la prose libre : il est **composé à partir de champs**
(type, surface, pièces, ville, prix, options). Le thème le **rédige** donc directement
dans chaque langue, au lieu de le traduire.

C'est gratuit, instantané, sans clé API, et la qualité est maîtrisée : aucune phrase
bancale de traducteur automatique.

### Exemple — appartement 120 m², Maarif, Casablanca

**Français**
> Propriétaire vend appartement de 120 m², 3 chambres + salon ou plus à Maarif,
> Casablanca. Au prix de 1 850 000 MAD. Contact direct, sans commission. *(148 car.)*

**Anglais**
> Owner selling apartment of 120 sqm, 3 bedrooms + living room or more in Maarif,
> Casablanca. Priced at 1 850 000 MAD. Direct contact, no commission. *(147 car.)*

**Arabe**
> المالك يبيع شقة بمساحة 120 م²، 3 غرف نوم وصالون أو أكثر في الدار البيضاء. بثمن
> 1 850 000 درهم. تراس، بدون مقابل، مرآب، مصعد. بدون عمولة. *(144 car.)*

Les trois restent sous la limite des 155 caractères.

### Les noms de villes sont traduits en arabe

Casablanca → **الدار البيضاء**, Rabat → **الرباط**, Agadir → **أكادير**,
Marrakech → **مراكش**, Tanger → **طنجة**… 37 villes couvertes.

Écrire « Casablanca » en lettres latines au milieu d'une phrase arabe casse la lecture
**et** le référencement : personne ne tape la recherche ainsi.

---

## Le SEO multilingue

Chaque page porte ses balises `hreflang`, vérifiées en production :

```
<link rel="alternate" hreflang="fr" href="…/property/appartement…">
<link rel="alternate" hreflang="en" href="…/en/property/apartment…">
<link rel="alternate" hreflang="ar" href="…/ar/property/شقة…">
<link rel="alternate" hreflang="x-default" href="…/property/appartement…">
```

**Exactement 4 balises par page, aucun doublon**, sur les trois langues.

C'est ce qui dit à Google : *« ce sont les mêmes biens en trois langues, pas du contenu
dupliqué »*. Chaque version peut alors se classer sur son marché.

La page arabe est servie en **`lang="ar"` + `dir="rtl"`**, avec son H1 et sa meta en arabe.

---

## Deux bugs trouvés pendant les tests

**1. Les pages EN et AR renvoyaient une 404.** Les pages existaient bien en base, mais les
règles de réécriture de WordPress ne connaissaient pas encore les URL traduites. Corrigé :
le thème régénère ces règles automatiquement au premier dépôt multilingue. Revérifié en
supprimant volontairement les règles — les trois langues répondent 200 sans intervention.

**2. Dix balises `hreflang` au lieu de quatre.** Polylang et le thème en émettaient chacun,
avec des formats contradictoires (`fr` et `fr-FR` pour la même URL) — une erreur que Google
signale. Le thème laisse désormais Polylang gérer, et ajoute seulement le `x-default`
manquant.

Trois défauts de rédaction corrigés au passage : « 5e étage » non traduit (devient
*5th floor* / الطابق 5), liaison manquante avant « terrasse », et un accord arabe incorrect
(`مساحته` → `بمساحة`, valable au masculin comme au féminin).

---

## Ce que vous avez choisi

| Point | Comportement |
|---|---|
| Traduction | Automatique par gabarit, à la publication |
| Mot personnel | Recopié tel quel dans les 3 versions |
| Publication | Les 3 versions partent en ligne ensemble à la validation WhatsApp |

Les données factuelles (prix, surface, photos, taxonomies) sont partagées : une seule
saisie, trois pages cohérentes.

---

## Installation

1. **Apparence › Thèmes › Ajouter › Téléverser** → `partikulier-6.8.0.zip`
2. Vérifiez que Polylang a bien **fr, en, ar** configurées
3. Par sécurité au premier lancement : **Réglages › Permaliens › Enregistrer**

Les annonces déjà publiées ne sont pas traduites rétroactivement — la règle s'applique aux
nouveaux dépôts. Je peux écrire un script de rattrapage pour vos 20 annonces existantes si
vous le souhaitez.

---

## Une remarque honnête

La rédaction par gabarit donne un texte **correct et bien référencé**, mais volontairement
factuel. C'est le bon choix pour des centaines d'annonces générées automatiquement.

Le seul texte vraiment « humain » est le mot personnel de l'annonceur, recopié tel quel —
donc en français sur les pages arabe et anglaise, comme vous l'avez demandé. Si un jour ce
mélange vous gêne, on pourra le masquer hors de sa langue d'origine.
