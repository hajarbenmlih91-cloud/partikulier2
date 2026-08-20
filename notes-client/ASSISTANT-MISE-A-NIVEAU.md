# Assistant de mise à niveau · v6.9.0

## D'abord, la réponse à votre question

**C'est vous qui devez le faire — je n'ai aucun accès à votre site.**

Je travaille sur une copie de test dans mon environnement. Votre base Hostinger, vos
20 annonces, vos taxonomies : je ne les vois pas et je ne peux pas y écrire.

C'est d'ailleurs préférable. Personne ne devrait pouvoir modifier votre base sans que
vous l'ayez déclenché vous-même.

**Mais je vous ai retiré tout le risque.**

---

## Un seul écran, plus de scripts à lancer

Nouveau menu : **Partikulier › Mise à niveau**

Un badge indique le nombre d'étapes restantes. L'écran affiche les trois opérations dans
l'ordre, et **verrouille chacune tant que la précédente n'est pas terminée**.

Plus de risque d'inverser l'ordre : c'est le code qui l'empêche.

### Étape 1 — Remettre les villes dans la bonne taxonomie

> 4 termes sont rangés dans « Achat ou location » alors qu'ils n'y ont pas leur place.
> *Agadir · Beni Mellal · El Jadida · Essaouira*
>
> [ **Déplacer ces villes vers « Ville ou quartier »** ]

Un clic. Une confirmation vous demande si la base est sauvegardée.

### Étape 2 — Réassigner « À vendre » ou « À louer »

Reste manuelle, volontairement : **moi seul ne peux pas deviner** lesquelles de vos
annonces sont en vente et lesquelles sont en location. Le bouton ouvre directement la
liste des annonces, avec la marche à suivre en une phrase.

### Étape 3 — Créer les versions arabe et anglaise

Déverrouillée seulement quand les deux précédentes sont finies. Un clic crée toutes les
traductions manquantes.

---

## L'état affiché est mesuré, pas déclaré

L'écran ne coche pas une case « fait ». À chaque affichage, il **compte réellement** :

- les termes mal classés dans `es_category`
- les annonces sans action commerciale
- les annonces sans traduction

Si un problème réapparaît, l'étape se rouvre automatiquement.

---

## Testé de bout en bout

J'ai reproduit votre situation exacte — villes rangées dans `es_category` — puis exécuté
le cycle complet dans un vrai navigateur.

| Test | Résultat |
|---|---|
| Détection du problème | 4 termes mal classés, correctement listés |
| Étapes 2 et 3 au départ | **Verrouillées** |
| Tentative de forcer l'étape 3 | **Refusée côté serveur** |
| Clic réel sur l'étape 1 | Villes déplacées vers `es_location` |
| Étape 2 après réparation | Se déverrouille, signale 4 annonces sans action |
| Étape 3 après réassignation | Crée les 3 langues avec le lieu correct |
| État final | **0 étape en attente**, tout affiché « ✓ Terminé » |
| Erreurs PHP | **0** |

Exemple de résultat après le cycle complet :

```
fr : Studio calme proche des gares
en : Studio of 28 sqm for sale in Rabat
ar : استوديو بمساحة 28 م² للبيع في الرباط
```

Le lieu est présent dans les trois langues — c'est exactement ce que l'ordre des étapes
garantit.

---

## Ce que vous avez à faire

1. Installez **`partikulier-6.9.0.zip`**
2. **Sauvegardez votre base** — phpMyAdmin › Exporter
3. Allez dans **Partikulier › Mise à niveau**
4. Suivez les étapes dans l'ordre affiché

Comptez cinq minutes, dont l'essentiel sur l'étape 2 (réassigner vos 20 annonces).

---

## Les scripts restent disponibles

`tests/reparer-taxonomies.php` et `tests/traduire-annonces.php` sont toujours livrés,
avec leur mode simulation. Si vous préférez voir le détail ligne par ligne avant
d'agir, ils restent le meilleur outil.

L'assistant fait la même chose, en plus sûr et sans URL à taper.

Une fois tout terminé, l'écran vous invite à supprimer le dossier `tests/` de votre
serveur. Le thème fonctionne sans lui.
