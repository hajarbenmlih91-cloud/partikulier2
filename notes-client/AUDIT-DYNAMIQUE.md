# Audit dynamique — WordPress réel, tests exécutés

Vous aviez raison : l'analyse statique ne dit rien du comportement réel. J'ai monté un
WordPress complet (PHP 8.4, MariaDB, Estatik, Polylang, 345 annonces) et **attaqué le site**
au lieu de lire le code.

Trois familles de tests qu'un dev senior lance avant tout cahier des charges.

---

## 1. Tests de sécurité — on attaque, on ne lit pas

`node tests/securite.mjs` — 15 scénarios avec de vraies sessions authentifiées.

Deux comptes propriétaires (Marc, Sofia), une annonce chacun. Marc tente d'agir sur celle
de Sofia.

### Faille trouvée et corrigée : statut HTTP invalide sur les refus

| Scénario | Avant | Après |
|---|---|---|
| Marc supprime l'annonce de Sofia | **HTTP 200** | HTTP 403 |
| Marc modifie l'annonce de Sofia | **HTTP 200** | HTTP 403 |
| Action hors liste blanche (`drop_database`) | **HTTP 200** | HTTP 400 |

**La protection métier fonctionnait** — le message « Vous n'êtes pas autorisé » était bien
renvoyé et l'action n'était pas exécutée. Mais le code HTTP était 200, ce qui veut dire
qu'un client JavaScript testant `response.ok` aurait cru l'opération réussie.

**Cause** : `(int) $result->get_error_data('status')`. `get_error_data()` retourne le
**tableau** `array('status' => 403)`, et `(int)` d'un tableau vaut `1` — pas 403. WordPress
recevait un statut invalide et retombait sur 200.

Corrigé dans `class-dashboard.php` et `class-owner-insights.php` (deux occurrences).

### Les 12 autres scénarios : conformes

IDOR après correction, nonce invalide rejeté, action légitime autorisée, accès admin refusé
à un auteur, octroi premium réservé à l'administrateur, gestion anonyme bloquée, dépôt sans
nonce refusé, **injection SQL** sans effet, **XSS réfléchi** non exécuté, énumération
d'utilisateurs impossible, `wp-config.php` / `debug.log` / `package.json` inaccessibles.

**Résultat : 15/15.**

---

## 2. Tests de charge — 345 annonces

### Le vrai problème : un N+1 mesuré

En faisant varier le nombre d'annonces affichées :

| Annonces affichées | Requêtes SQL |
|---|---|
| 1 | 63 |
| 5 | 75 |
| 10 | 90 |
| 20 | 120 |
| 40 | **180** |

**+3 requêtes SQL par annonce affichée.** C'est la signature d'un N+1.

### D'où viennent-elles

Traçage par origine sur `/property/` :

| Source | Requêtes |
|---|---|
| **Plugin Estatik** | **163** |
| Cœur WordPress | 17 |
| Thème Partikulier | **0** |

**Le thème n'est pas en cause.** Le préchargement des métadonnées est actif
(`update_post_meta_cache = true`), les `get_post_meta` des cartes sont donc servis depuis le
cache. Les 163 requêtes viennent d'Estatik.

C'est une information décisive pour le cahier des charges : ce chantier relève du plugin,
pas du thème. Le corriger demande soit de contourner Estatik, soit de le remplacer.

### Temps de réponse (345 annonces, local)

| Page | Médiane | Cartes |
|---|---|---|
| Accueil | 195 ms | 6 |
| Archive p1 | 244 ms | 40 |
| Archive p2 | 250 ms | 40 |
| Filtre par type | 179 ms | 2 |

10 requêtes simultanées : **10/10 en HTTP 200**, 106 ms par requête.

Les temps restent bons — mais en local, sans latence réseau. Avec 5 000 annonces et un
hébergement mutualisé, 180 requêtes par page deviendront un problème.

---

## 3. Tests fonctionnels — les parcours réels

### Pagination : fonctionne, mais 40 par page

Réglage WordPress : 10 par page. Réalité mesurée : **40**, imposé par Estatik.

Vérifié avec 45 annonces : page 1 → 40 résultats, page 2 → 5 résultats, HTTP 200. La
pagination est correcte, seule la valeur surprend.

### Filtres de recherche : fonctionnent

| Paramètre | Résultats (sur 15) |
|---|---|
| `?es_type=appartement` | 2 |
| `?es_type=maison` | 2 |
| `?es_city=casablanca` | 1 |
| `?es_action=a-vendre` | 4 |
| `?es_price_max=1000000` | 3 |

**Correction d'une erreur que j'ai commise** : mon premier test utilisait `?q=` et `?type=`
et concluait que « la recherche ne filtre rien ». C'était faux — ces paramètres n'existent
pas. Les vrais sont `es_type`, `es_city`, `es_action`, `es_price_max`, et ils fonctionnent.

Je le signale parce qu'un test mal écrit produit un faux bug, qui coûte aussi cher qu'un
vrai bug manqué.

### Formulaire de dépôt

28 champs servis, 9 obligatoires, aucune erreur JavaScript. La validation navigateur ne
bloque pas la soumission à vide — à vérifier côté serveur (le handler AJAX refuse bien sans
nonce, testé en sécurité).

---

## Ce que ces tests changent pour le cahier des charges

Par priorité, chiffres à l'appui :

1. **Statut HTTP des refus** — corrigé pendant cet audit. Aurait pu laisser croire à un
   client JS qu'une suppression refusée avait réussi.
2. **N+1 d'Estatik : 163 requêtes SQL par page.** Le poste le plus lourd. À arbitrer :
   contourner, remplacer le plugin, ou mettre en cache agressif.
3. **Polylang masque les annonces** (voir `AUDIT-AVANT-CAHIER-DES-CHARGES.md`) — bloquant.
4. **40 annonces par page** imposées par Estatik, contre 10 réglés dans WordPress.

---

## Ce qui reste non testé

- **Charge réelle** : 345 annonces testées, pas 5 000. Le seuil de rupture est inconnu.
- **Envoi de photos** : le dépôt avec upload réel n'a pas été exécuté de bout en bout.
- **Vérification WhatsApp** : dépend d'un service externe non configuré.
- **Navigateurs** : tout est mesuré sous Chromium. Safari et Firefox non testés, notamment
  pour `:has()`.
- **Paiement / premium** : la logique existe, le parcours d'achat n'a pas été joué.
- **Sauvegarde et restauration** : jamais testées.

Ces six points sont eux-mêmes des lignes du cahier des charges.

---

## Rejouer ces tests

```bash
bash setup-stack.sh
php -S 0.0.0.0:8090 -t wp wp/router.php &
cd theme && npm run setup

node tests/securite.mjs   # 15 scenarios d'attaque
node tests/parcours.mjs   # parcours utilisateurs
node tests/charge.mjs     # montee en charge
npm test                  # non-regression visuelle
```
