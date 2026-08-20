# Questions fréquentes

Les questions qu'on se pose en reprenant ce projet, avec des réponses directes. Si ta question n'y est pas, `04-PIEGES.md` couvre les bugs et `02-DECISIONS.md` les « pourquoi ».

---

## Démarrage

### Par où je commence ?

```bash
bash scripts/install.sh    # ~2 minutes, installe tout
bash scripts/start.sh      # http://localhost:8090
```

Administration : `admin` / `admin`.

### Le site tourne, mais je modifie le thème et rien ne change.

Tu as édité `theme/`, mais WordPress lit dans `wp/wp-content/themes/partikulier/`. Après **chaque** modification :

```bash
bash scripts/sync.sh
```

C'est l'erreur la plus fréquente du projet.

### Comment je vérifie mon travail avant de livrer ?

```bash
bash scripts/check.sh
```

Syntaxe PHP et JS, cohérence des 4 fichiers de version, et régressions déjà rencontrées. Code de sortie 1 si quelque chose cloche — utilisable en CI.

### Comment je fabrique une nouvelle version ?

```bash
bash scripts/package.sh 6.14.0
```

Aligne les versions, lance le contrôle, produit le zip et affiche son SHA-256. Ajoute l'entrée de changelog dans `theme/readme.txt` à la main.

---

## Problèmes courants

### Le formulaire refuse toutes les annonces : « dépôt momentanément indisponible »

Le numéro WhatsApp n'est pas configuré. *Apparence › Personnaliser › Validation WhatsApp*. Le message ne mentionne pas WhatsApp, d'où la confusion. `install.sh` le pré-remplit avec un numéro fictif.

### Les URL d'annonces renvoient 404

*Réglages › Permaliens › Enregistrer*, sans rien modifier. Ou en ligne de commande :

```bash
cd wp && wp rewrite flush --hard
```

### Le cœur des favoris ne devient pas rouge

Deux causes possibles, dans cet ordre :

1. `.pk-wish-active` n'a pas de style CSS — `check.sh` le détecte.
2. **Deux gestionnaires de clic** sont attachés au bouton et s'annulent. C'était le vrai bug historique. Cherche `addEventListener` sur `.pk-card-wishlist` : il ne doit y en avoir **qu'un**.

### L'upload de photos ne fonctionne plus

Cherche un second script qui réinitialise le champ (`fileInput.value = ""`). `main.js` et `submit-steps.js` coexistent sur la page de dépôt ; `main.js` se neutralise via `if (document.querySelector('.pk-steps')) return;`. Si cette garde saute, l'upload casse.

### L'admin WordPress crache des erreurs JavaScript

Normal avec `php -S` : `wp is not defined`, `setLocaleData`… Pour vérifier que ce n'est pas ton code, ouvre `/wp-admin/index.php` (tableau de bord natif) et compare. S'il en produit autant ou plus, c'est l'environnement.

### `php -l` échoue sur absolument tous les fichiers

PHP n'est pas installé, ou a disparu après une reconstruction du conteneur. Relance `bash scripts/install.sh`.

### Ma base a été corrompue par des tests, je veux repartir propre

```bash
sudo mariadb -e "DROP DATABASE wp;"
rm -rf wp
bash scripts/install.sh
```

---

## Comprendre le code

### Où est la logique d'une annonce, du dépôt à la publication ?

1. `inc/class-form.php` — validation, création, upload des photos.
2. `inc/class-listing-preview.php` — titre et description générés.
3. `inc/class-whatsapp-verification.php` — code de validation.
4. `inc/class-listing-approval.php` — écran admin, identifiants, webhook n8n.
5. `inc/class-listing-translations.php` — versions AR et EN.
6. `inc/class-listing-urls.php` — URL géographique et redirections.

### Pourquoi le CPT s'appelle `properties` ?

C'est le nom réel du plugin Estatik. Ni `estate_property` ni `property`. Les taxonomies sont `es_type` (studio, villa…), `es_category` (à vendre / à louer) et `es_location` (villes **et** quartiers mélangés, taxonomie plate).

### Comment savoir si un terme `es_location` est une ville ou un quartier ?

La taxonomie ne le dit pas. Il faut interroger le référentiel :

```php
Partikulier_Morocco_Places::reference();  // array( 'Casablanca' => array( 'Maarif', … ) )
```

C'est ce que fait `Partikulier_Listing_URLs::city_of_district()`.

### Pourquoi certaines annonces n'apparaissent pas dans les listes admin ?

Les traductions automatiques portent `_pk_auto_translation` et sont filtrées partout — sinon chaque annonce apparaîtrait en trois exemplaires. Pense à ce filtre si tu ajoutes une liste.

### Où sont les réglages du thème ?

Tout dans une seule option, `pk_theme_options` :

```bash
cd wp && wp option get pk_theme_options --format=json
```

Interface : *Apparence › Personnaliser*, et *Partikulier › Personnalisation du site* pour les textes.

### Comment ajouter un module ?

Créer `inc/class-mon-module.php` avec une classe statique qui s'auto-initialise (`Partikulier_Mon_Module::init();` en fin de fichier), puis l'ajouter au tableau de `functions.php`. L'ordre compte : infrastructure d'abord, écrans d'admin ensuite.

---

## n8n et identifiants

### Comment tester le webhook sans n8n ?

Un faux serveur de 3 lignes suffit :

```bash
mkdir -p /tmp/n8n && cat > /tmp/n8n/hook.php <<'EOF'
<?php
file_put_contents(__DIR__.'/received.json', file_get_contents('php://input'));
http_response_code(200); echo '{"ok":true}';
EOF
cd /tmp/n8n && nohup php -S 127.0.0.1:9099 hook.php >/dev/null 2>&1 &
```

Configure `http://127.0.0.1:9099` comme URL de webhook, valide une annonce, puis lis `/tmp/n8n/received.json`.

### Pourquoi le mot de passe part-il en clair sur WhatsApp ?

Décision produit du client, prise en connaissance de cause. Il veut que l'annonceur retrouve ses accès dans sa messagerie à tout moment. La version précédente utilisait un lien à usage unique, refusé pour cette raison. Détails et compromis possible dans `02-DECISIONS.md`.

### Le webhook ne part pas alors que l'URL est configurée

Si l'URL pointe vers une adresse locale, sache que `wp_http_validate_url()` les refuse — c'est pourquoi le code valide la forme à la main. Vérifie ensuite la méta `_pk_n8n_error` sur l'annonce :

```bash
cd wp && wp post meta get <ID> _pk_n8n_error
```

### Un annonceur a perdu son mot de passe

*Partikulier › Valider les annonces › Renvoyer des identifiants › Nouveau mot de passe*. L'ancien cesse aussitôt de fonctionner.

### Pourquoi n8n reçoit-il parfois un mot de passe vide ?

C'est voulu. Si l'annonceur a déjà reçu ses identifiants, une revalidation ne les régénère pas — sinon il serait enfermé dehors. Le champ `send_credentials` passe à `false` : n8n doit alors envoyer un message de mise en ligne **sans** identifiants.

---

## SEO

### Je change le slug d'une annonce, l'URL ne bouge pas comme prévu

La ville et le quartier sont **figés** dans `_pk_url_city` et `_pk_url_district` à l'enregistrement, pour que les URL indexées ne changent jamais toutes seules. Pour forcer un recalcul :

```bash
cd wp && wp eval 'Partikulier_Listing_URLs::store_geo( 123 );'
```

### Comment vérifier que les redirections fonctionnent ?

```bash
curl -s -o /dev/null -w "%{http_code} -> %{redirect_url}\n" \
  "http://localhost:8090/property/mon-annonce/"
```

Attendu : `301` vers `/annonce/ville/quartier/mon-annonce/`. Un `302` serait un bug — Google ne transfère pas l'historique sur une redirection temporaire.

### Pourquoi une URL avec une fausse ville redirige-t-elle au lieu de renvoyer 404 ?

Pour éviter le contenu dupliqué. Sans cela, la même annonce existerait sous une infinité d'adresses répondant toutes 200, et le signal SEO serait divisé.

---

## Livraison

### Que faut-il faire chez le client après l'installation ?

1. Activer le thème (les pages obligatoires se créent seules).
2. Installer et activer Estatik.
3. *Réglages › Permaliens › Enregistrer* — **obligatoire**.
4. Renseigner le numéro WhatsApp.
5. Vérifier avec *Partikulier › Diagnostic des pages › Analyser tout le site*.

### Comment vérifier un site en production rapidement ?

L'outil de diagnostic intégré teste les 6 pages clés et signale les problèmes bloquants. C'est le premier réflexe.

### Le client peut-il modifier les textes sans développeur ?

Oui : *Partikulier › Personnalisation du site*. C'était une exigence du projet.
