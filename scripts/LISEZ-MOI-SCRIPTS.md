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

`wp eval-file` **ne transmet pas de manière fiable les arguments positionnels aux scripts**. Les scripts d’application utilisent donc une convention unique : dry-run par défaut, puis `PK_APPLIQUER=1 wp --path=wp eval-file ...` pour appliquer. La sortie JSON indique toujours explicitement `mode: dry-run` ou `mode: apply`.

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

## Procédure de qualification 6.14.1

Pour une recette complète avec Estatik, Polylang et Query Monitor, installer les plugins dans une sandbox fraîche, démarrer WordPress, puis exécuter dans cet ordre :

```bash
wp --path=wp eval-file scripts/provision-polylang.php
wp --path=wp rewrite flush
wp --path=wp eval-file scripts/report-polylang.php
wp --path=wp eval-file scripts/report-taxonomy-invariants.php
node scripts/real-ui-evidence.mjs
node scripts/test-estatik-interactions.mjs
bash scripts/check.sh
node scripts/visual.mjs check
bash scripts/package.sh 6.14.1
```

`provision-polylang.php` configure FR/EN/AR, relie les familles de traductions et appelle les réparations nécessaires. `provision-polylang-taxonomies.php` et `repair-all-translation-terms.php` sont disponibles lorsque les traductions existent déjà mais que les relations de termes Estatik sont incomplètes. `report-taxonomy-invariants.php` vérifie les relations WordPress brutes indépendamment de la langue active dans WP-CLI.

## Profilage senior

`senior-http-profiler.php` est un mu-plugin temporaire. Il collecte le nombre de requêtes, le temps SQL, la mémoire et les motifs répétés avec leur premier appelant. Il doit être copié temporairement dans `wp/wp-content/mu-plugins/`, avec `SAVEQUERIES` activé, puis supprimé après la mesure. Les versions 6.13.1 et 6.14.1 doivent être mesurées avec la même base, les mêmes plugins, les mêmes URLs, les mêmes langues et le même ordre de parcours.

Le verdict N+1 doit distinguer une régression du thème d’une répétition interne à Polylang. Les rapports de référence sont `rapport-query-monitor-6.13.1.jsonl`, `rapport-query-monitor-6.14.1.jsonl`, `rapport-query-monitor-before-6.13.1.jsonl` et `rapport-query-monitor-after-6.14.1.jsonl`.

## Livrables de passation

La documentation complète se trouve dans `documentation/passation-6.14.1/`. Les sorties brutes restent à la racine sous forme de `rapport-*`. Le package installable est généré avec `bash scripts/package.sh 6.14.1`. Après génération, calculer son empreinte avec `sha256sum partikulier-6.14.1.zip` et reporter cette empreinte dans le rapport de livraison.


## Réconciliation Polylang D-bis

`reconcile-polylang-orphans.php` réalise un inventaire non destructif par défaut des auto-traductions éjectées de leur groupe après remplacement manuel. Le mode d’application doit être explicitement demandé avec `PK_APPLIQUER=1` et uniquement après sauvegarde de la base.

`migrate-polylang-source-meta.php` migre les anciennes valeurs langue de `_pk_translation_source` vers l’ID source, conserve la valeur legacy et liste les groupes ambigus. `test-polylang-migration.php` teste le dry-run puis l’application.

`test-polylang-sync-e2e.php` appelle le vrai `sync()` et couvre FR/EN/AR, l’auto seule, le remplacement manuel et `invalid_source_meta`.

`test-polylang-orphan-replacement.php` crée une source FR, une auto EN, puis une traduction manuelle EN dans la même famille et vérifie que l’auto devient `draft` tandis que la manuelle reste `publish`.

`test-polylang-auto-only.php` vérifie le cas inverse : une auto-traduction qui reste la seule version de sa langue demeure `publish`. Ces scénarios sont obligatoires avant de signer le lot D.
