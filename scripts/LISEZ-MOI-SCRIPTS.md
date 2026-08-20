# Les scripts

## Scripts shell — le quotidien

| Script | Rôle |
| --- | --- |
| `install.sh` | Monte tout l'environnement : PHP, MariaDB, WP-CLI, WordPress fr_FR, Estatik, thème, 6 annonces avec photos. Idempotent. |
| `start.sh` | Démarre le site sur le port 8090 (`PK_PORT=9000` pour en changer). Relance MariaDB si besoin. |
| `sync.sh` | Recopie `theme/` vers WordPress et purge le cache. **Après chaque édition.** |
| `check.sh` | Syntaxe PHP et JS, cohérence des 4 fichiers de version, régressions connues. Sort en code 1 si problème. |
| `package.sh <version>` | Aligne les versions, contrôle, produit le zip et son SHA-256. |

Variables d'environnement reconnues : `PK_PORT` (défaut 8090) et `PK_WP_DIR` (défaut `./wp`).

## Scripts de test navigateur

Nécessitent Node et Playwright :

```bash
npm run setup     # npm install + navigateur Chromium
```

**Important** : ces scripts écrivent leurs captures dans `tests/__screens__/` **relativement au dossier courant**. Lance-les depuis la racine du paquet :

```bash
npm run test:rapide     # 4 vues, ~6 s — pour itérer
npm test                # 12 vues (6 pages × 2 tailles), comparaison pixel
npm run test:parcours   # parcours de dépôt complet
npm run test:audit      # performance, accessibilité, SEO
npm run test:securite   # contrôles de sécurité
```

Après une modification visuelle **volontaire et validée**, réenregistrer la référence :

```bash
npm run test:baseline
```

Sans cela, les comparaisons pixel échoueront en signalant ton changement comme une régression.

Le site doit tourner (`bash scripts/start.sh`) avant de lancer ces tests. Adresse personnalisable via `PK_BASE`.

## Utilitaires PHP

À lancer avec WP-CLI depuis le dossier `wp/` :

```bash
cd wp
wp eval-file ../scripts/diagnostic-site.php       # diagnostic complet en console
wp eval-file ../scripts/reparer-taxonomies.php    # corrige les termes mal classés
wp eval-file ../scripts/traduire-annonces.php     # rattrape les traductions manquantes
```

`wp eval-file` **ne transmet pas les arguments positionnels**. Les scripts qui attendent une confirmation lisent une variable d'environnement, par exemple `PK_APPLIQUER=1`.

## Simuler n8n en local

```bash
mkdir -p /tmp/n8n && cat > /tmp/n8n/hook.php <<'EOF'
<?php
file_put_contents(__DIR__.'/received.json', file_get_contents('php://input'));
http_response_code(200); echo '{"ok":true}';
EOF
cd /tmp/n8n && nohup php -S 127.0.0.1:9099 hook.php >/dev/null 2>&1 &
```

Renseigner `http://127.0.0.1:9099` comme URL de webhook, valider une annonce, puis lire `/tmp/n8n/received.json`.
