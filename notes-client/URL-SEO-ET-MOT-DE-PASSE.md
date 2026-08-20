# Version 6.13.0 — URL géographiques et mot de passe WhatsApp

Archive : `partikulier-6.13.0.zip` (523 Ko)
SHA-256 : `861de7c6d71710444fe6dda1fc753850839a910ee3b69773ed1c98633045ec46`

---

## 1. Les URL contiennent la ville et le quartier

**Avant**

```
/property/appartement-lumineux-maarif/
```

**Maintenant**

```
/annonce/casablanca/maarif/appartement-lumineux-maarif/
```

Deux gains, et le second est le plus important.

D'abord, `property` était un mot anglais hérité du plugin ; `annonce` parle à vos visiteurs marocains. Ensuite et surtout, **la géographie figure désormais dans le chemin de l'URL**. Quand quelqu'un tape « appartement à vendre Maarif Casablanca », Google trouve ces mots dans l'adresse, dans le titre, dans le fil d'Ariane et dans la description : le signal se renforce au lieu de se disperser.

Sans quartier connu, l'URL reste courte et propre : `/annonce/rabat/mon-bien/`.

### Les redirections, sans lesquelles tout cela serait dangereux

Changer ses URL sans redirections, c'est perdre d'un coup les positions acquises. **Toutes les anciennes adresses répondent en 301**, la redirection permanente que Google comprend comme « cette page a déménagé, transfère-lui son historique ».

Vérifié en conditions réelles :

| Adresse demandée | Réponse |
| --- | --- |
| `/annonce/casablanca/maarif/mon-bien/` | 200 — la bonne page |
| `/property/mon-bien/` (ancienne) | 301 vers la nouvelle |
| `/annonce/casablanca/mon-bien/` (quartier oublié) | 301 vers la nouvelle |
| `/annonce/nawak/mon-bien/` (ville fantaisiste) | 301 vers la nouvelle |

Ce dernier cas compte plus qu'il n'y paraît. Sans lui, n'importe qui — ou n'importe quel robot — pourrait faire exister votre annonce sous des dizaines d'adresses différentes répondant toutes 200. Google appelle cela du contenu dupliqué et divise le signal entre les copies. Ici, **une annonce n'a qu'une seule adresse valide**, toutes les autres y ramènent.

Le **sitemap**, la balise **canonical** et le **JSON-LD** utilisent la nouvelle adresse — vérifié sur le site.

### Un détail qui évite des URL laides

Les accents et apostrophes sont translittérés proprement : `Tétouan` → `tetouan`, `Fès` → `fes`, `M'Hamid` → `m-hamid`. Sans ce traitement, WordPress produit parfois des slugs mutilés du type `ttouan`.

Enfin, la ville et le quartier sont **figés au moment de l'enregistrement**. Si vous renommez un terme dans six mois, les URL déjà indexées ne bougent pas — un site dont les adresses changent tout seules perd son référencement.

---

## 2. Le mot de passe part sur WhatsApp

J'ai remplacé le lien à usage unique par ce que vous avez demandé : **l'identifiant et le mot de passe, en clair, dans le message WhatsApp**.

Votre raisonnement se tient : l'annonceur retrouve ses accès dans sa messagerie quand il veut, sans dépendre d'un lien expiré. C'est plus simple pour lui, et vous n'avez pas de relances à gérer.

Ce que n8n reçoit désormais :

```json
"account": {
  "login": "hicham45985477",
  "password": "xMTXGHCPP8",
  "login_url": "https://votresite.ma/wp-login.php",
  "send_credentials": true
}
```

**Le mot de passe est conçu pour être recopié depuis un téléphone.** Il exclut les caractères qui se confondent — le O et le zéro, le I majuscule, le l minuscule et le 1. Rien n'est plus agaçant qu'un mot de passe qu'on ne parvient pas à retaper.

Deux protections que j'ai ajoutées :

**Revalider une annonce ne réinitialise plus le mot de passe.** Si vous repassez sur une annonce déjà publiée, un annonceur qui utilise ses accès depuis des semaines — ou qui a changé son mot de passe lui-même — se retrouverait dehors sans comprendre pourquoi. Le champ `send_credentials` passe alors à `false` : n8n sait qu'il doit envoyer un message de mise en ligne, sans identifiants.

**Un bouton « Nouveau mot de passe »** dans l'écran d'administration, pour l'annonceur qui a perdu son message WhatsApp. Vous générez, n8n renvoie, l'ancien cesse de fonctionner.

### Ce que ce choix implique, en toute franchise

Vous devez le savoir pour décider en connaissance de cause : **le mot de passe reste lisible dans la conversation WhatsApp**, indéfiniment. Si le téléphone de l'annonceur est perdu, prêté ou consulté par un tiers, l'accès à son compte l'est aussi.

Côté site, il n'y a pas de faille : WordPress ne stocke que l'empreinte du mot de passe, jamais sa version lisible — vérifié. Le risque se situe entièrement sur le téléphone de l'annonceur.

Un compromis existe si vous changez d'avis un jour : envoyer le mot de passe **et** inviter à le changer à la première connexion. L'annonceur garde le message comme filet de sécurité, mais le mot de passe qui traîne dans WhatsApp devient rapidement obsolète. Dites-moi si vous voulez que je l'ajoute — c'est une heure de travail.

---

## Après la mise en ligne

Une seule action de votre part : allez dans **Réglages › Permaliens** et cliquez sur **Enregistrer**, sans rien modifier. Cela régénère les règles d'URL de WordPress. Le thème le fait automatiquement à l'activation, mais ce clic garantit le résultat sur un hébergement mutualisé.

Ensuite, dans la Search Console, soumettez de nouveau votre sitemap. Google mettra quelques semaines à basculer vers les nouvelles adresses — c'est normal, les 301 font le travail pendant ce temps.

---

## Ce qu'il reste

1. requêtes N+1 côté Estatik (temps de chargement) ;
2. Polylang : 20 annonces publiées, 5 visibles ;
3. les 40 annonces par page imposées.

Le chantier SEO structurant est terminé. Le prochain gain visible pour vos visiteurs serait la vitesse de chargement.
