# Analyse de votre diagnostic

Merci, ce rapport contient la réponse. **Le thème est correctement installé. Le problème
vient de la classification de vos données.**

---

## Le vrai problème : `es_category` contient des villes

Votre rapport, section taxonomies :

```
es_category    : 20 termes  (actions (A vendre, A louer))
     - A louer                      0 annonces      <-- 0 !
     - À vendre                     0 annonces      <-- 0 !
     - Agadir                       1 annonces
     - Beni Mellal                  1 annonces
     - Casablanca                   1 annonces
     - El Jadida                    1 annonces
     ... et 8 autres
```

`es_category` est la taxonomie des **actions commerciales** : « À vendre » / « À louer ».
Chez vous elle contient **18 villes**, et les deux vrais termes ont **0 annonce**.

### Conséquences directes, que vous voyez à l'écran

1. Le menu **« Achat ou location »** du moteur de recherche affiche des noms de villes
2. Le filtre par action ne renvoie **jamais** rien
3. Les cartes n'affichent **aucun badge** « À VENDRE » / « À LOUER »

J'ai reproduit votre situation exactement sur mon environnement. Résultat obtenu, identique
au vôtre :

```
es_action  -> ['Tout', 'Agadir', 'Beni Mellal', 'Casablanca', 'El Jadida', 'Essaouira', 'Fes']
```

**Le code fait ce qu'on lui demande.** Il lit `es_category` pour remplir ce menu. Si des
villes y sont rangées, elles s'affichent.

### Comment vos données en sont arrivées là

Vos villes sont **en double** : les 17 termes de `es_location` (correct) sont aussi présents
dans `es_category` (incorrect). C'est la signature d'un import où la colonne « ville » a été
envoyée vers les deux taxonomies.

---

## Un point positif : `es_type` est propre

```
es_type        : 14 termes
     - Appartement   8 annonces
     - Maison        6 annonces
     - Chalet        1 · Duplex 1 · Loft 1 · Studio 1
```

Aucune ville ici. Le menu « Type de bien » est donc correct **depuis la v6.4.2** — avant
cette version il était simplement vide (bug corrigé : la fonction retournait sans affichage).

Restent quelques termes parasites d'Estatik en anglais, à 0 annonce : *Appartements,
Condos, Maisons, Maisons de ville, Multifamily*. Sans effet visible, mais vous pouvez les
supprimer pour faire propre.

---

## L'outil de réparation

`tests/reparer-taxonomies.php`, livré dans la **v6.4.3**.

### Étape 1 — Simulation (ne modifie rien)

```
https://VOTRE-SITE.com/wp-content/themes/partikulier/tests/reparer-taxonomies.php
```

Il liste ce qu'il ferait, sans rien toucher :

```
MODE : SIMULATION (rien n est modifie)
Termes dans es_category : 20
Termes qui n y ont pas leur place : 18
  "Agadir" (1 annonce)     -> serait deplace vers es_location
  "Beni Mellal" (1 annonce) -> serait deplace vers es_location
  ...
```

### Étape 2 — Sauvegardez votre base

Chez Hostinger : *hPanel › Bases de données › phpMyAdmin › Exporter*. Non négociable.

### Étape 3 — Application réelle

```
https://VOTRE-SITE.com/wp-content/themes/partikulier/tests/reparer-taxonomies.php?appliquer=1
```

Pour chaque ville mal classée, il : crée la ville dans `es_location` si absente, rattache
les annonces, retire l'association fautive, supprime le terme vide de `es_category`, puis
vide le cache du thème.

**Aucune annonce n'est supprimée.** Testé sur mon environnement : 6 termes déplacés,
6 annonces mises à jour, 0 perte.

---

## Effet de bord à connaître — important

Après réparation, vos annonces **n'auront plus d'action** (ni « À vendre » ni « À louer »),
puisque ce champ contenait une ville. L'outil vous le dit explicitement :

```
ATTENTION : 20 annonce(s) n ont plus d action (A vendre / A louer).
```

**Il faut ensuite assigner l'action à chaque annonce.** Avec 20 annonces, c'est faisable
rapidement : *Annonces › cocher tout › Actions groupées › Modifier › Catégorie › À vendre*,
puis corriger individuellement celles qui sont en location.

Je peux automatiser cette étape si vous me dites comment distinguer vos ventes de vos
locations (par exemple : un prix mensuel sous 20 000 MAD = location).

---

## Le reste du rapport est bon

| Contrôle | État |
|---|---|
| Thème actif | Partikulier **v6.4.2** — bien installé |
| Fichiers | `style.css` 72,5 Ko, à jour du 17/08 02:04 |
| Polylang | 3 langues, **0 annonce sans langue** — le problème que je craignais n'existe pas chez vous |
| Cache | 0 fichier, aucun plugin de cache — rien ne fausse l'affichage |
| CSS additionnel | 0 caractère — aucune surcharge |
| PHP | 8.3.30 — version récente |

Un point à surveiller : **20 annonces publiées, 5 visibles en front**. C'est normal si la
page d'accueil n'en affiche que 5, mais vérifiez que l'archive `/property/` les liste bien
toutes.

---

## Récapitulatif

1. Installez **`partikulier-6.4.3.zip`**
2. Lancez la **simulation** de réparation
3. **Sauvegardez la base**
4. Lancez la réparation avec `?appliquer=1`
5. Réassignez « À vendre » / « À louer » à vos 20 annonces
6. Videz les caches, Ctrl + F5

Envoyez-moi une capture après ces étapes — le menu « Achat ou location » devrait afficher
« À vendre » et « À louer », et les badges réapparaître sur les cartes.
