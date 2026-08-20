# Pourquoi votre site ne ressemble pas à mon sandbox

Question juste, et c'est la plus importante. Réponse en deux parties : le bug des villes,
et la raison de fond.

---

## 1. Les villes dans « Type de bien » — reproduit et expliqué

**Ce n'est pas un bug de code. Ce sont vos données.**

J'ai reproduit exactement votre symptôme en rangeant 5 villes dans la taxonomie des types :

```
<option value="beni-mellal">Beni Mellal</option>
<option value="essaouira">Essaouira</option>
<option value="mohammedia">Mohammedia</option>
<option value="ouarzazate">Ouarzazate</option>
<option value="rabat">Rabat</option>
```

Le thème lit la taxonomie `es_type` pour remplir « Type de bien ». Si des villes y ont été
créées — par un import, une saisie manuelle ou une manipulation d'Estatik — elles
apparaissent forcément dans ce menu. Le code fait ce qu'on lui demande.

**Indice qui confirme** : votre capture du pied de page montrait déjà, sous
« TYPES DE BIENS », les entrées *Appartement, Chalet, Duplex, Loft* — mais votre bloc
« CONTACT » listait *Rabat, Mohammedia, Beni Mellal, Ouarzazate, Essaouira*. Les deux
taxonomies sont mélangées sur votre installation.

### Comment le corriger chez vous

*Annonces → Types de biens* (taxonomie `es_type`) : supprimez les termes qui sont des
villes. Recréez-les dans *Annonces → Localisations* (`es_location`) si nécessaire, puis
réassignez les annonces concernées.

Un outil de diagnostic est livré pour les repérer automatiquement — voir plus bas.

---

## 2. Trois vrais bugs de code trouvés au passage

En enquêtant sur ce symptôme, j'ai trouvé trois défauts dans le champ de recherche du
header. Corrigés en **v6.4.2** :

| # | Bug | Effet |
|---|---|---|
| A | `Partikulier_Geo::property_type_options()` **retourne** une chaîne mais était appelée sans `echo` | Le menu « Type de bien » du header était **entièrement vide** |
| B | Le champ s'appelait `pk_type`, alors que les filtres lisent `es_type` | Même rempli, le menu n'aurait **rien filtré** |
| C | Le formulaire pointait vers l'accueil (`home_url('/')`) | La recherche n'atteignait **jamais** l'archive |

Les trois se cumulaient : le menu du header était décoratif, il ne pouvait pas fonctionner.

Vérifié après correction : `?es_type=appartement` → 2 annonces, `?es_type=maison` → 2.

---

## 3. La vraie raison : mon sandbox n'est pas votre site

C'est le point de fond, et vous avez raison de le soulever.

| | Mon sandbox | Votre site |
|---|---|---|
| Données | 6 annonces générées par script | Vos annonces réelles |
| Taxonomies | Termes propres créés par moi | Termes hérités, mélangés |
| Extensions | Estatik + Polylang | **Inconnues de moi** |
| Hébergement | PHP local, serveur intégré | Hostinger, cache serveur |
| Cache | Vidé à chaque test | WP Rocket ? LiteSpeed ? |
| CSS additionnel | Aucun | Possible via Personnaliser |
| Thème enfant | Aucun | Possible |

**Je n'ai jamais vu votre site.** Je travaille depuis vos captures d'écran et un
environnement que j'ai fabriqué. Chaque écart entre les deux est un angle mort.

C'est exactement ce qui s'est produit ici : mon sandbox affichait un menu correct parce que
mes taxonomies étaient propres. Le vôtre affiche des villes parce que les vôtres ne le sont
pas. Aucun test de mon côté ne pouvait le détecter.

---

## 4. L'outil qui comble cet angle mort

`tests/diagnostic-site.php` — à exécuter **sur votre site**, en lecture seule.

```bash
# en SSH
wp eval-file wp-content/themes/partikulier/tests/diagnostic-site.php
```

Ou, sans SSH : déposez le fichier à la racine WordPress, appelez-le une fois dans le
navigateur en étant connecté administrateur, **puis supprimez-le**.

Il rapporte :

- version de WordPress, PHP, du thème, et **du fichier CSS réellement servi**
- **toutes les extensions actives** avec leur version
- **les termes mal classés** — villes dans `es_type`, types dans `es_location`
- le nombre d'annonces publiées **et combien sont réellement visibles**
- l'état Polylang et les annonces sans langue assignée
- les plugins de cache actifs, le CSS additionnel du Personnaliseur
- la date de modification des fichiers clés du thème

Sortie testée sur mon environnement, avec détection correcte des 5 villes mal classées :

```
>> TERMES MAL CLASSES : Beni Mellal (1), Essaouira (1), Mohammedia (1),
   Ouarzazate (1), Rabat (1)
   Corrigez-les dans Annonces > es_type (renommer ou supprimer).
```

**Envoyez-moi cette sortie.** C'est le seul moyen pour moi de voir votre installation telle
qu'elle est, plutôt que de deviner.

---

## Ce que je propose

1. Installez **`partikulier-6.4.2.zip`** (corrige les 3 bugs du header)
2. Lancez `diagnostic-site.php` et envoyez-moi le résultat
3. Nettoyez les taxonomies d'après son rapport

Les autres sujets — 5 000 annonces, upload photo, WhatsApp, Safari/Firefox, paiement,
sauvegarde — restent en attente comme convenu.
