# Page « Déposer une annonce » — corrigé

Vous aviez raison, et ce n'était pas WhatsApp. **La page n'existait tout simplement pas.**

---

## Ce qui se passait

Le thème fournissait le gabarit du formulaire, et le bouton « Déposer une annonce » du
menu pointait vers `/deposer-une-annonce/`… mais **rien ne créait jamais cette page**.

Résultat : le lien menait vers une adresse inexistante → **404 / page vide**. D'où votre
« j'ai rien ».

Reproduit sur mon environnement en supprimant la page :

```
AVANT   HTTP /deposer-une-annonce/ -> 404      formulaire : 0
APRÈS   HTTP /deposer-une-annonce/ -> 200      formulaire : 1
```

C'était un **trou dans le thème**, pas une erreur de votre part.

---

## Ce que j'ai corrigé — v6.4.4

### 1. Les pages se créent toutes seules

Nouveau module `inc/class-required-pages.php`. À l'activation du thème, il crée
« Déposer une annonce » et « Mes annonces », et leur rattache le bon gabarit.

Si une page est supprimée plus tard, une **alerte rouge apparaît dans l'admin** avec un
bouton **« Créer les pages manquantes »**. Testé : suppression → alerte → 1 clic → réparé.

Compatible Polylang : la page est rattachée à votre langue par défaut.

### 2. Prix en MAD

Le formulaire demandait « Prix (€) » alors que tout le site est en dirhams. Corrigé en
**« Prix (MAD) »**, exemple `1 250 000`.

### 3. Le message d'erreur WhatsApp devient utile

Il y a un **second blocage** en aval, que vous auriez rencontré juste après : tant que le
numéro WhatsApp de validation n'est pas renseigné, **aucune annonce ne peut être publiée**.

Vous aviez choisi de garder cette validation. Le message disait seulement « Contactez
l'administrateur » — inutile quand l'administrateur, c'est vous. Désormais, connecté en
admin, vous lisez :

> Publication impossible : le numéro WhatsApp de validation n'est pas renseigné. Allez dans
> **Apparence › Personnaliser › Validation WhatsApp** et saisissez votre numéro au format
> international (ex : 212612345678).

Les visiteurs, eux, voient un message neutre — on n'expose pas vos réglages internes.

---

## Ce que vous devez faire

1. **Installez `partikulier-6.4.4.zip`**
2. **Réactivez le thème** (Apparence › Thèmes) — c'est ce qui déclenche la création des
   pages. Si vous préférez ne pas réactiver, l'alerte admin et son bouton font le même travail.
3. **Renseignez votre numéro WhatsApp** : *Apparence › Personnaliser › Validation WhatsApp*,
   format international sans espaces, ex. `212612345678`. **Sans ça, aucune publication ne
   passera.**
4. Testez le dépôt d'une annonce.

---

## Vérifié de bout en bout

| Test | Résultat |
|---|---|
| `/deposer-une-annonce/` | **200**, formulaire affiché |
| Les 19 champs du formulaire | tous présents |
| Menus déroulants | action 3, type 11, ville 10 — remplis |
| Devise | **MAD** |
| Dépôt sans numéro WhatsApp | refus avec message explicite |
| Dépôt avec numéro | **annonce créée**, statut `pending`, code `PK-42-…` |
| Page supprimée → réparation | alerte, puis 0 manquante |
| Accueil / archive | 200, aucune régression |
| Erreurs PHP | **0** |

---

## Reste en attente

Le menu **« Achat ou location »** affiche toujours des villes chez vous. C'est le problème
de données identifié précédemment — le code est correct, ce sont vos taxonomies qui sont
mélangées. Il se règle en lançant `tests/reparer-taxonomies.php`
(voir `ANALYSE-DE-VOTRE-SITE.md`), après sauvegarde de la base.
