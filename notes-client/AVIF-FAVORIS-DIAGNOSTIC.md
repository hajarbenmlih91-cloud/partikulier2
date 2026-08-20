# AVIF, favoris et diagnostic global · v6.11.0

---

## 1. La conversion AVIF

### Elle est immédiate, sur le serveur

Deux déclencheurs, dans `class-avif.php` :

```php
add_action( 'add_attachment', 'convert_original' );          // à l'ajout
add_filter( 'wp_generate_attachment_metadata', 'generate_avif' ); // sur chaque taille
```

Dès qu'une photo est envoyée, le thème génère l'AVIF **au même endroit**, en ajoutant
l'extension :

```
photo.jpg        ← l'original, conservé
photo.jpg.avif   ← la version légère, créée à côté
```

Trois méthodes sont tentées dans l'ordre : l'éditeur d'images WordPress, puis `avifenc`,
puis `vips`. Si aucune n'est disponible, le site continue de servir le JPEG — pas d'image
cassée.

### Non, l'original n'est jamais supprimé — et c'est volontaire

J'ai vérifié : la seule suppression du code concerne **un fichier AVIF raté** (0 octet),
jamais votre photo.

C'est un choix que je défends :

- **Tous les navigateurs ne lisent pas l'AVIF.** Le thème sert une balise `<picture>` avec
  l'AVIF en premier et le JPEG en repli. Supprimer l'original casserait l'affichage pour
  ces visiteurs.
- **WordPress a besoin de l'original** pour régénérer les miniatures, recadrer, ou si
  vous changez de thème un jour.
- **La conversion n'est pas toujours réversible** sans perte.

### Conséquence pour votre espace disque

Vos images occupent **plus** de place, pas moins : original + AVIF + les tailles générées
par WordPress. Ce qui diminue, c'est le **poids transféré au visiteur** — souvent 50 à 70 %
de moins. C'est ça qui compte pour la vitesse et le référencement.

Si l'espace disque devient un problème chez Hostinger, dites-le moi : je peux ajouter une
option qui supprime les originaux après un délai, mais je ne l'activerais pas par défaut.

---

## 2. Les favoris — et un bug trouvé au passage

### Où ils sont stockés

**Dans le navigateur du visiteur** (`localStorage`), sous la clé `pk_wishlist`.
Aucun compte, aucune inscription.

En parallèle, le site enregistre un **compteur anonyme** côté serveur : un identifiant de
visiteur haché, sans nom ni e-mail, uniquement pour dire au propriétaire *« 12 personnes
ont enregistré votre annonce »*. Purge automatique après 90 jours.

### Ce que cela implique

| | |
|---|---|
| Change d'appareil | Les favoris **ne suivent pas** |
| Efface ses données de navigation | Les favoris **disparaissent** |
| Navigation privée | Perdus à la fermeture |
| Aucun compte requis | **Avantage** : zéro friction |

C'est le bon compromis pour un portail sans inscription obligatoire. Si vous voulez un
jour des favoris qui suivent le visiteur, il faudra un compte — on en reparlera.

### Le bug : le cœur ne menait nulle part

Vous me l'aviez signalé, et vous aviez raison sur toute la ligne. Le cœur du header
pointait vers :

```php
href="<?php echo esc_url( pk_properties_archive_url() ); ?>"   // ← l'archive !
```

**Il n'existait aucune page favoris.** Le clic renvoyait vers la liste de toutes les
annonces.

**Corrigé** : nouvelle page **Favoris**, créée automatiquement comme les autres. Testée
au navigateur :

```
1. Page vide      → « Aucune annonce enregistrée pour l'instant »
2. Deux cœurs     → localStorage : ["45","46"]
3. Retour favoris → 2 cartes affichées, « 2 biens enregistrés depuis cet appareil »
```

Zéro erreur JavaScript. La page prévient honnêtement que les favoris sont liés à
l'appareil.

---

## 3. Le diagnostic — oui, tout le site en un clic

Nouveau bouton **« Analyser tout le site »** dans *Partikulier › Diagnostic des pages*.

Résultat réel sur mon environnement de test :

```
1 problème bloquant et 3 avertissements sur l'ensemble du site.

✓  Accueil                          Conforme                    [Détail]
!  Toutes les annonces              1 à améliorer               [Détail]
   4 annonces attendent une validation
✓  Déposer une annonce              Conforme                    [Détail]
!  Mes annonces                     1 à améliorer               [Détail]
✓  Favoris                          Conforme                    [Détail]
✕  Fiche annonce                    1 bloquant, 1 à améliorer   [Détail]
   4 image(s) sans texte alternatif
```

Un bandeau donne le total, chaque ligne affiche le premier problème rencontré, et le
bouton **Détail** ouvre l'analyse complète de la page.

Les six pages couvertes : accueil, archive des annonces, déposer, mes annonces, favoris,
fiche annonce. Vous pouvez aussi taper le nom d'une page WordPress quelconque.

---

## Installation

**`partikulier-6.11.0.zip`**

`SHA-256 : 84193f166411b1a1d3903df26f09b958e2f05e0f825c2465a95fadf21933bfb7`

Après installation, l'alerte vous proposera de **créer la page Favoris** manquante — un
clic, comme pour les autres.

Versions alignées : `style.css` = `package.json` = `readme.txt` = **6.11.0**.
0 erreur PHP, 0 erreur JavaScript.
