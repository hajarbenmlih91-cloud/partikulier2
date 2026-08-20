# Version 6.12.0 — cœur rouge et validation des dépôts

Archive : `partikulier-6.12.0.zip` (519 Ko)
SHA-256 : `ab47ff9b91a13783307ccdc0e1ed1847d03fc3bd827671890971b5e911d6952c`

---

## 1. Le cœur devient rouge

**Ce qui bloquait.** Deux causes, pas une seule.

D'abord, la classe `pk-wish-active` était bien posée par le JavaScript, mais **aucune règle CSS ne lui était associée** : le navigateur ajoutait une classe qui ne signifiait rien visuellement.

Ensuite — et c'est le vrai problème — **deux gestionnaires de clic étaient attachés au même cœur**. Le premier ajoutait l'annonce aux favoris, le second la retirait aussitôt. Résultat net : rien ne se passait, et `localStorage` restait vide. C'est ce qui explique pourquoi l'enregistrement semblait fonctionner par moments seulement.

**Ce qui a été fait.** Un seul gestionnaire pilote désormais le clic ; le second bloc ne conserve que le compteur serveur anonyme. Le cœur rouge est défini en CSS, plus par des styles en ligne, donc la feuille de style reste maîtresse de l'apparence.

**Comportement vérifié en navigateur :**

| Action | Résultat |
| --- | --- |
| Clic sur le cœur | Rouge plein `#e0245e` + légère pulsation |
| Rechargement de la page | Reste rouge |
| Second clic | Redevient vide, favori retiré |
| Erreurs JavaScript | Aucune |

L'accessibilité suit : `aria-pressed` bascule et le libellé passe de « Ajouter aux favoris » à « Retirer des favoris ». La pulsation se désactive pour les personnes ayant demandé la réduction des animations.

---

## 2. Écran de validation des dépôts

**Partikulier › Valider les annonces**, avec une pastille indiquant le nombre d'annonces en attente.

Chaque ligne montre le titre, la date, le nom et le téléphone de l'annonceur, ainsi que son code WhatsApp. Deux boutons : **Publier** ou **Refuser**.

Un garde-fou : si le lieu proposé n'est pas encore validé, le bouton Publier est remplacé par « Validez d'abord le lieu ». Cela évite de publier une annonce rattachée à un quartier fantôme.

À la validation, l'annonce passe en ligne **dans les trois langues d'un coup**, le cache est purgé, et les identifiants s'affichent immédiatement à l'écran — utile si n8n est momentanément indisponible : vous pouvez les copier à la main.

---

## 3. Les identifiants transmis à n8n

Conformément à votre consigne, **aucun mot de passe en clair n'existe nulle part** : ni sur WhatsApp, ni dans la base, ni dans les journaux.

n8n reçoit deux choses : le **nom d'utilisateur**, et un **lien de définition du mot de passe**.

```json
{
  "event": "listing_approved",
  "listing": { "id": 38, "title": "…", "url": "…", "price": "6500" },
  "owner":   { "name": "…", "phone": "0669876543", "email": "…" },
  "account": {
    "login": "sara2749216",
    "password_link": "https://…/?pk_login=…&uid=3",
    "link_ttl_hours": 48
  }
}
```

**Le lien est sérieusement protégé**, et cela a été testé :

- il est **haché en base** (`wp_hash_password`) — même quelqu'un qui lirait la base ne pourrait pas le reconstituer ;
- il est **à usage unique** : rejoué une seconde fois, il ne connecte plus ;
- il **expire au bout de 48 heures** ;
- un jeton erroné est rejeté.

L'annonceur clique, arrive directement sur la page de choix de son mot de passe, et le définit lui-même. Vous ne le connaissez jamais — c'est exactement ce qu'on attend d'un système propre.

---

## 4. Les deux canaux vers n8n

Comme demandé, les deux fonctionnent ensemble.

**Webhook sortant, immédiat.** À la validation, la charge ci-dessus part vers l'URL configurée dans *Apparence › Personnaliser › Validation WhatsApp › URL du webhook n8n*, authentifiée par l'en-tête `X-Partikulier-Automation`. Le résultat est mémorisé sur l'annonce : date d'envoi si tout va bien, message d'erreur sinon.

**Route de rattrapage.** Si n8n était hors service :

```
GET /wp-json/partikulier/v1/approved-listings
En-tête : X-Partikulier-Automation: votre-secret
```

Elle renvoie les validations des 72 dernières heures, avec un indicateur `webhook_sent` permettant à n8n de ne traiter que ce qu'il a manqué. Sans le secret, elle répond 401. Elle ne contient **jamais** de lien de mot de passe : les liens ne circulent que par le webhook, une seule fois.

Une note pratique : `wp_http_validate_url()` de WordPress refuse les adresses locales. Si votre n8n est auto-hébergé sur le même serveur, cette vérification a été assouplie — la forme de l'URL reste contrôlée, mais un n8n en local passe désormais.

---

## Où en est le reste

Toujours en attente, par ordre d'impact :

1. **URL propres incluant ville et quartier** — le point SEO le plus rentable encore ouvert ;
2. requêtes N+1 côté Estatik ;
3. Polylang : 20 annonces publiées, 5 visibles ;
4. les 40 annonces par page imposées.

Dites-moi lequel vous voulez traiter ensuite — je recommande les URL.
