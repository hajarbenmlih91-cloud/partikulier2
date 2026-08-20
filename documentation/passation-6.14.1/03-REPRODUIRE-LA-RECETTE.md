# Reproduire l’installation et la recette

## Pré-requis

La recette cible Ubuntu avec PHP CLI, MariaDB, WP-CLI, Node.js, npm et un navigateur Chromium compatible Playwright. Le dépôt doit être cloné dans un répertoire de travail. Les commandes ci-dessous sont prévues pour la sandbox du dépôt ; dans un autre environnement, adapter le chemin WordPress et les identifiants de base.

## Installation de base

```bash
gh repo clone hajarbenmlih91-cloud/partikulier2
cd partikulier2
bash scripts/install.sh
```

Le script installe WordPress et prépare le thème de démonstration. Vérifier ensuite les versions et lancer le contrôle statique :

```bash
php -v
wp --path=wp core version
bash scripts/check.sh
```

## Installation des plugins de recette

Dans une sandbox WordPress fraîche, installer Estatik, Polylang et Query Monitor. La version d’Estatik doit rester celle utilisée pendant la recette de référence ; ne pas la mettre à jour silencieusement entre l’avant et l’après.

```bash
wp --path=wp plugin install estatik --activate
wp --path=wp plugin install polylang --activate
wp --path=wp plugin install query-monitor --activate
```

Démarrer ensuite le serveur local :

```bash
bash scripts/start.sh
```

Le site est disponible sur `http://localhost:8090`. L’administration de démonstration utilise le compte créé par `scripts/install.sh`.

## Provisioning Polylang et Estatik

Le provisioning configure les trois langues, les types et taxonomies traduisibles, les familles de traductions et les relations de termes :

```bash
wp --path=wp eval-file scripts/provision-polylang.php | tee rapport-provision-local.json
wp --path=wp rewrite flush
wp --path=wp eval-file scripts/report-polylang.php | tee rapport-polylang-local.json
wp --path=wp eval-file scripts/report-taxonomy-invariants.php | tee rapport-taxonomy-local.json
```

Le résultat attendu est `fr`, `en` et `ar`, avec un décompte positif dans chaque langue. Le contrôle de taxonomies doit afficher `status: PASS`, `missing_action_ids: []` et `missing_location_ids: []`. Si les traductions existent déjà mais que les termes sont manquants, exécuter :

```bash
wp --path=wp eval-file scripts/repair-all-translation-terms.php
wp --path=wp eval-file scripts/report-taxonomy-invariants.php
```

## Contrôle HTTP et SEO

```bash
for u in /annonces/ /en/annonces/ /ar/annonces/ \
  /annonce/casablanca/appartement-lumineux-3-pieces/ \
  /deposer-une-annonce/ /favoris/; do
  curl -sS -o /dev/null -w "$u %{http_code}\\n" "http://localhost:8090$u"
done
curl -sS -I http://localhost:8090/property/
curl -sS -I http://localhost:8090/property/page/2/
```

Les trois archives linguistiques et les parcours principaux doivent répondre en 200. Les anciennes archives doivent répondre en 301 vers la route canonique. Vérifier dans le HTML la canonical, les hreflang, `MAD`, `MA` et l’absence de séparateurs vides dans les descriptions SEO.

## Test UI Estatik

Installer les dépendances JavaScript puis lancer les deux harnais :

```bash
npm install --no-audit --no-fund
node scripts/real-ui-evidence.mjs
node scripts/test-estatik-interactions.mjs
```

Le premier rapport doit couvrir `archive`, `detail`, `depot` et `favoris`, sans erreur console. Le second doit afficher `status: PASS`, un favori ajouté puis supprimé de `localStorage`, et une galerie détectée sur la fiche.

## Profilage Query Monitor et Xdebug

Query Monitor doit rester activé pendant les mesures. Pour ajouter la collecte détaillée, copier temporairement le mu-plugin :

```bash
mkdir -p wp/wp-content/mu-plugins
cp scripts/senior-http-profiler.php wp/wp-content/mu-plugins/
wp --path=wp config set SAVEQUERIES true --raw
```

Pour obtenir une trace Xdebug indépendante :

```bash
mkdir -p /tmp/xdebug-senior
(cd wp && nohup env XDEBUG_MODE=profile \
  XDEBUG_CONFIG='output_dir=/tmp/xdebug-senior' \
  php -d xdebug.mode=profile \
      -d xdebug.output_dir=/tmp/xdebug-senior \
      -S 0.0.0.0:8091 router.php >/tmp/xdebug-server.log 2>&1 &)
```

Figer une liste d’URLs et ne jamais la modifier entre les versions. Pour comparer 6.13.1 et 6.14.1, remplacer uniquement le contenu de `wp/wp-content/themes/partikulier/`, sans réinstaller la base ni les plugins. Enregistrer séparément les sorties JSONL avant et après. Comparer par URL : nombre total de requêtes, temps SQL, mémoire et motifs SQL répétés avec appelant.

## Baseline visuelle

La baseline propre 6.14.1 se génère ainsi :

```bash
node scripts/visual.mjs baseline
node scripts/visual.mjs check
```

Le harnais couvre 6 pages en desktop et mobile, soit 12 vues. Ne régénérer la baseline qu’après avoir figé le dataset, les images, les langues, les URLs et la version du navigateur. Une baseline recréée avec d’autres données ne prouve pas l’absence de régression historique.

## Packaging

```bash
bash scripts/package.sh 6.14.1
sha256sum partikulier-6.14.1.zip
```

Après modification, exécuter `git diff --check`, `bash scripts/check.sh`, les tests UI et la recette visuelle. Le package final doit être commité avec son empreinte SHA-256 et le rapport de qualification correspondant.
