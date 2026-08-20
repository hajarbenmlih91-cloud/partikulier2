# Après l'installation : lancer le diagnostic

**Bonne nouvelle : vous n'avez rien à déposer à la racine.** Le fichier est déjà dans le
zip que vous installez. Il suffit d'appeler une URL.

---

## Étape 1 — Installer le thème

*Apparence › Thèmes › Ajouter › Téléverser* → `partikulier-6.4.2.zip` → **Activer**.

Puis videz les caches :
- *Réglages › Partikulier* (ou supprimez le contenu de `wp-content/uploads/partikulier-cache/`)
- Si vous avez WP Rocket, LiteSpeed, WP Super Cache… videz-les aussi
- **Ctrl + F5** dans le navigateur

---

## Étape 2 — Rester connecté en administrateur

Important : le diagnostic refuse tout le monde sauf les administrateurs. Vérifié —
un visiteur anonyme et même un abonné connecté obtiennent `Reserve aux administrateurs.`

---

## Étape 3 — Ouvrir cette URL dans le même navigateur

```
https://VOTRE-SITE.com/wp-content/themes/partikulier/tests/diagnostic-site.php
```

Remplacez `VOTRE-SITE.com` par votre domaine. D'après vos captures, ce serait :

```
https://blanchedalmond-reindeer-376379.hostingersite.com/wp-content/themes/partikulier/tests/diagnostic-site.php
```

Vous obtenez une page de texte brut, environ 60 lignes.

---

## Étape 4 — Me l'envoyer

Sélectionnez tout (Ctrl + A), copiez (Ctrl + C), collez-le dans votre message.
Ou faites une capture d'écran.

---

## Étape 5 — Supprimer le fichier (optionnel)

Le fichier ne présente pas de risque : il est en lecture seule, réservé aux
administrateurs, et n'affiche aucun mot de passe ni clé (vérifié).

Si vous préférez le retirer malgré tout, supprimez via votre gestionnaire de fichiers
Hostinger ou FTP :

```
wp-content/themes/partikulier/tests/
```

Attention : ce dossier contient aussi les tests automatisés. Le supprimer n'affecte pas le
fonctionnement du site, mais vous devrez le réinstaller pour relancer un diagnostic.

---

## Ce que le rapport contient

```
DIAGNOSTIC PARTIKULIER — 2026-08-17 01:56 UTC
--------------------------------------------------------------------
WordPress          : 7.0.4
PHP                : 8.4.24
Theme actif        : Partikulier v6.4.2
style.css          : 73 Ko, modifie le 2026-08-17
Constante version  : 6.4.2

EXTENSIONS ACTIVES
  Estatik                                4.3.4
  ...

TAXONOMIES — verifier que chaque terme est au bon endroit
es_type        : 16 termes  (types de biens)
  >> TERMES MAL CLASSES : Beni Mellal (1), Essaouira (1), Rabat (1)...
     Corrigez-les dans Annonces > es_type

CONTENU
Annonces publiees  : 344
Visibles en front  : 5

MULTILINGUE
Polylang actif     : oui — fr, en, ar
Annonces sans langue : 0

CACHES ET SURCHARGES
Cache du theme     : 2 fichiers
CSS additionnel (Personnaliser) : 0 caracteres
```

Les lignes commençant par `>>` signalent un problème détecté.

---

## Si ça ne marche pas

| Symptôme | Cause probable |
|---|---|
| `Reserve aux administrateurs.` | Vous n'êtes pas connecté en admin dans **ce** navigateur |
| Erreur 404 | Le thème n'est pas installé sous le nom `partikulier`, ou le dossier `tests/` a été exclu |
| Page blanche | Votre hébergeur bloque l'exécution de PHP hors des fichiers WordPress standards |
| `wp-load.php introuvable` | Structure d'installation inhabituelle — dites-le moi |

Dans tous ces cas, dites-le-moi et je vous donne une alternative (par exemple un plugin
temporaire à installer, ou une requête à lancer depuis phpMyAdmin).

---

## Une correction apportée en préparant ces instructions

Ma consigne précédente — « déposez-le à la racine » — **aurait échoué**. Le fichier
calculait le chemin vers `wp-load.php` en remontant exactement 4 dossiers, ce qui n'est
correct que depuis `wp-content/themes/partikulier/tests/`. Depuis la racine, il serait
remonté 4 niveaux trop haut et aurait produit une erreur fatale.

Corrigé : le fichier cherche maintenant `wp-load.php` en remontant l'arborescence.
Testé aux deux emplacements, avec un compte admin, un abonné et un anonyme.
