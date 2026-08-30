#!/bin/bash
# ==============================================================================
# Partikulier — installation complète de l'environnement de développement
#
# Monte : PHP, MariaDB, WP-CLI, WordPress (fr_FR), Estatik, le thème et un jeu
# de données de démonstration (6 annonces avec photos, pages, menu).
#
# Idempotent : relançable autant de fois que nécessaire.
#
#   bash scripts/install.sh
#
# Testé sur Debian/Ubuntu. Sur une autre distribution, adapter l'étape 1.
# ==============================================================================
set -Eeuo pipefail

WP_VERSION="${PK_WP_VERSION:-7.1}"
WPCLI_VERSION="${PK_WPCLI_VERSION:-2.12.0}"
ESTATIK_VERSION="${PK_ESTATIK_VERSION:-4.3.4}"
POLYLANG_VERSION="${PK_POLYLANG_VERSION:-3.8.7}"
QUERY_MONITOR_VERSION="${PK_QUERY_MONITOR_VERSION:-4.0.7}"
DB_NAME="${PK_DB_NAME:-wp}"
DB_USER="${PK_DB_USER:-wp}"
DB_PASS="${PK_DB_PASS:-wp}"
ADMIN_USER="${PK_ADMIN_USER:-admin}"
ADMIN_PASS="${PK_ADMIN_PASS:-local-admin-change-me}"
ADMIN_EMAIL="${PK_ADMIN_EMAIL:-admin@example.test}"

case "$DB_NAME" in ''|*[!A-Za-z0-9_]*) echo "DB_NAME invalide : utiliser seulement A-Z, a-z, 0-9 et _" >&2; exit 2;; esac
case "$DB_USER" in ''|*[!A-Za-z0-9_]*) echo "DB_USER invalide : utiliser seulement A-Z, a-z, 0-9 et _" >&2; exit 2;; esac
DB_PASS_SQL="$(printf '%s' "$DB_PASS" | sed "s/'/''/g")"

# Racine du paquet (le dossier qui contient ce script/..)
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_SRC="$ROOT/theme"
CORE_SRC="$ROOT/partikulier-core"
WP_DIR="${PK_WP_DIR:-$ROOT/wp}"
ESTATIK_ZIP="${PK_ESTATIK_ZIP:-$ROOT/vendor-artifacts/estatik-${ESTATIK_VERSION}.zip}"
ESTATIK_URL="${PK_ESTATIK_URL:-https://downloads.wordpress.org/plugin/estatik.zip}"
ESTATIK_SHA256="${PK_ESTATIK_SHA256:-}"
POLYLANG_ZIP="${PK_POLYLANG_ZIP:-$ROOT/vendor-artifacts/polylang-${POLYLANG_VERSION}.zip}"
QUERY_MONITOR_ZIP="${PK_QUERY_MONITOR_ZIP:-$ROOT/vendor-artifacts/query-monitor-${QUERY_MONITOR_VERSION}.zip}"
ESTATIK_REPRODUCIBLE=1
PORT="${PK_PORT:-8090}"
URL="http://localhost:$PORT"
LOG="$ROOT/install.log"

# Échec rapide : une recette sans artefact vérifiable ne doit pas provisionner
# une base et un WordPress partiels avant de découvrir le défaut.
if [ -f "$ESTATIK_ZIP" ]; then
  [ -f "${ESTATIK_ZIP}.sha256" ] || { echo "Checksum Estatik absent : ${ESTATIK_ZIP}.sha256" >&2; exit 2; }
elif [ -n "$ESTATIK_SHA256" ]; then
  [ -n "$ESTATIK_URL" ] || { echo "PK_ESTATIK_URL manquant avec PK_ESTATIK_SHA256" >&2; exit 2; }
elif [ "${PK_ALLOW_UNPINNED_ESTATIK:-0}" != 1 ]; then
  echo "Artefact Estatik vérifié absent ; fallback non reproductible non activé" >&2
  exit 2
fi
for dependency_zip in "$POLYLANG_ZIP" "$QUERY_MONITOR_ZIP"; do
  [ -f "$dependency_zip" ] || { echo "Dépendance vendor absente : $dependency_zip" >&2; exit 2; }
  [ -f "${dependency_zip}.sha256" ] || { echo "Checksum dépendance absente : ${dependency_zip}.sha256" >&2; exit 2; }
  sha256sum --check --strict "${dependency_zip}.sha256"
done

export DEBIAN_FRONTEND=noninteractive
echo "=== $(date) ===" > "$LOG"
step(){ echo ">>> $1" | tee -a "$LOG"; }
have(){ command -v "$1" >/dev/null 2>&1; }

if [ ! -d "$THEME_SRC" ]; then
  echo "ERREUR : $THEME_SRC est introuvable."
  echo "Dézippez le bundle dans un dossier contenant theme/."
  exit 1
fi
if [ ! -f "$CORE_SRC/partikulier-core.php" ]; then
  echo "ERREUR : $CORE_SRC/partikulier-core.php est introuvable. Le core M0 est obligatoire."
  exit 1
fi

# ------------------------------------------------------------------ 1. paquets
step "1/7 paquets système"
if have apt-get; then
  sudo apt-get update -qq >>"$LOG" 2>&1
  sudo apt-get install -y -qq --no-install-recommends \
    php-cli php-mysql php-gd php-xml php-mbstring php-curl php-zip php-intl \
    mariadb-server mariadb-client curl unzip >>"$LOG" 2>&1
else
  echo "  apt-get absent : installez manuellement PHP 8+, MariaDB/MySQL, curl, unzip." | tee -a "$LOG"
fi

# ------------------------------------------------------------------ 2. mariadb
step "2/7 base de données"
sudo mkdir -p /run/mysqld /var/lib/mysql
sudo chown -R mysql:mysql /run/mysqld /var/lib/mysql 2>/dev/null
[ -d /var/lib/mysql/mysql ] || sudo mariadb-install-db --user=mysql --datadir=/var/lib/mysql >>"$LOG" 2>&1
pgrep -x mariadbd >/dev/null || (sudo mariadbd-safe --datadir=/var/lib/mysql --user=mysql >>"$LOG" 2>&1 &)
ready=0
for i in $(seq 1 30); do
  if sudo mariadb -e "SELECT 1" >/dev/null 2>&1; then ready=1; break; fi
  sleep 1
done
[ "$ready" -eq 1 ] || { echo "MariaDB indisponible après 30 secondes" | tee -a "$LOG" >&2; exit 1; }

sudo mariadb -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS_SQL';
CREATE USER IF NOT EXISTS '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS_SQL';
ALTER USER '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS_SQL';
ALTER USER '$DB_USER'@'127.0.0.1' IDENTIFIED BY '$DB_PASS_SQL';
GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'localhost'; GRANT ALL ON \`$DB_NAME\`.* TO '$DB_USER'@'127.0.0.1';
FLUSH PRIVILEGES;" >>"$LOG" 2>&1

# ------------------------------------------------------------------ 3. wp-cli
step "3/7 WP-CLI"
if ! have wp || ! wp --version 2>/dev/null | grep -q "WP-CLI $WPCLI_VERSION"; then
  curl -fsSL "https://github.com/wp-cli/wp-cli/releases/download/v$WPCLI_VERSION/wp-cli-$WPCLI_VERSION.phar" -o /tmp/wpcli
  chmod +x /tmp/wpcli && sudo mv /tmp/wpcli /usr/local/bin/wp
fi
[ "$(wp --version 2>/dev/null | sed -n 's/^WP-CLI \([^ ]*\).*/\1/p')" = "$WPCLI_VERSION" ] || { echo "WP-CLI version incorrecte" | tee -a "$LOG" >&2; exit 1; }

# ------------------------------------------------------------------ 4. coeur WP
step "4/7 WordPress"
mkdir -p "$WP_DIR" && cd "$WP_DIR" || exit 1
[ -f wp-settings.php ] || wp core download --version="$WP_VERSION" --locale=fr_FR --force >>"$LOG" 2>&1
[ "$(wp core version 2>/dev/null || true)" = "$WP_VERSION" ] || { echo "WordPress $(wp core version 2>/dev/null || echo unknown) != $WP_VERSION" | tee -a "$LOG"; exit 1; }
if [ ! -f wp-config.php ]; then
  wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost=127.0.0.1 \
    --locale=fr_FR --skip-check >>"$LOG" 2>&1
  wp config set WP_DEBUG true --raw >>"$LOG" 2>&1
  wp config set WP_DEBUG_LOG true --raw >>"$LOG" 2>&1
  wp config set WP_DEBUG_DISPLAY false --raw >>"$LOG" 2>&1
fi
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="$URL" --title="Partikulier" \
    --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email >>"$LOG" 2>&1
  wp language core install fr_FR >>"$LOG" 2>&1
  wp site switch-language fr_FR >>"$LOG" 2>&1
  wp rewrite structure '/%postname%/' --hard >>"$LOG" 2>&1
fi
# Une recette peut être relancée avec un port différent : les options d’URL
# doivent suivre l’instance courante, sinon WordPress redirige vers une ancienne.
wp option update home "$URL" >>"$LOG" 2>&1
wp option update siteurl "$URL" >>"$LOG" 2>&1

# Routeur : php -S ne gère pas seul les jolies URL.
cat > "$WP_DIR/router.php" <<'ROUTER'
<?php
$u = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$f = __DIR__ . $u;
if ($u !== '/' && file_exists($f) && !is_dir($f)) return false;
$_SERVER['SCRIPT_NAME'] = '/index.php';
require __DIR__ . '/index.php';
ROUTER

# ------------------------------------------------------------------ 5. thème
step "5/7 thème et plugin"
rm -rf wp-content/themes/partikulier
cp -r "$THEME_SRC" wp-content/themes/partikulier
if [ -d "$ROOT/mu-plugins" ]; then
  mkdir -p wp-content/mu-plugins
  cp -r "$ROOT/mu-plugins/." wp-content/mu-plugins/
fi
rm -rf wp-content/plugins/partikulier-core
mkdir -p wp-content/plugins/partikulier-core
cp -r "$CORE_SRC/." wp-content/plugins/partikulier-core/
wp plugin activate partikulier-core >>"$LOG" 2>&1
# Installation et activation robuste des plugins, avec versions contrôlées.
install_pinned_plugin() {
  local slug="$1" version="$2" source="$3"
  if ! wp plugin is-installed "$slug" >/dev/null 2>&1 || [ "$(wp plugin get "$slug" --field=version 2>/dev/null || true)" != "$version" ]; then
    wp plugin install "$source" --force >>"$LOG" 2>&1
  fi
  [ "$(wp plugin get "$slug" --field=version 2>/dev/null || true)" = "$version" ] || { echo "Plugin $slug != $version" | tee -a "$LOG"; exit 1; }
  wp plugin activate "$slug" >>"$LOG" 2>&1 || true
}
if [ -f "$ESTATIK_ZIP" ]; then
  checksum_file="${ESTATIK_ZIP}.sha256"
  [ -f "$checksum_file" ] || { echo "Checksum Estatik absent : $checksum_file" | tee -a "$LOG" >&2; exit 1; }
  ( cd "$(dirname "$ESTATIK_ZIP")" && sha256sum --check --strict "$(basename "$checksum_file")" ) | tee -a "$LOG"
  ESTATIK_SOURCE="$ESTATIK_ZIP"
elif [ -n "$ESTATIK_SHA256" ]; then
  curl -fsSL --retry 3 --retry-all-errors "$ESTATIK_URL" -o /tmp/estatik-${ESTATIK_VERSION}.zip
  printf '%s  %s\n' "$ESTATIK_SHA256" "/tmp/estatik-${ESTATIK_VERSION}.zip" | sha256sum --check --strict
  ESTATIK_SOURCE="/tmp/estatik-${ESTATIK_VERSION}.zip"
elif [ "${PK_ALLOW_UNPINNED_ESTATIK:-0}" = 1 ]; then
  echo "AVERTISSEMENT : Estatik utilise une URL générique sans checksum (mode non reproductible explicite)." | tee -a "$LOG" >&2
  ESTATIK_REPRODUCIBLE=0
  ESTATIK_SOURCE="$ESTATIK_URL"
else
  echo "Artefact Estatik vérifié absent. Fournir PK_ESTATIK_ZIP, ou PK_ESTATIK_URL + PK_ESTATIK_SHA256. Pour le fallback générique non reproductible, définir PK_ALLOW_UNPINNED_ESTATIK=1." | tee -a "$LOG" >&2
  exit 2
fi
install_pinned_plugin estatik "$ESTATIK_VERSION" "$ESTATIK_SOURCE"
if [ -f "$ESTATIK_SOURCE" ]; then
  ESTATIK_ACTUAL_SHA256="$(sha256sum "$ESTATIK_SOURCE" | awk '{print $1}')"
  echo "ESTATIK_SHA256=$ESTATIK_ACTUAL_SHA256" | tee -a "$LOG"
fi
[ "$ESTATIK_REPRODUCIBLE" -eq 1 ] || echo "ESTATIK_REPRODUCIBLE=0" >>"$LOG"
install_pinned_plugin polylang "$POLYLANG_VERSION" "$POLYLANG_ZIP"
install_pinned_plugin query-monitor "$QUERY_MONITOR_VERSION" "$QUERY_MONITOR_ZIP"

wp theme activate partikulier >>"$LOG" 2>&1

# Réglages sans lesquels le formulaire refuse tous les dépôts.
DEV_HMAC_SECRET="${PARTIKULIER_N8N_SECRET:-$(head -c 32 /dev/urandom | base64 -w0)}"
PK_INSTALL_HMAC_SECRET="$DEV_HMAC_SECRET" wp eval '
$o = get_option("pk_theme_options", array());
if (empty($o["whatsapp_validation_number"])) $o["whatsapp_validation_number"] = "212612345678";
if (empty($o["automation_api_secret"]))      $o["automation_api_secret"]      = getenv("PK_INSTALL_HMAC_SECRET");
update_option("pk_theme_options", $o);
do_action("after_switch_theme");
if (class_exists("Partikulier_Listing_URLs")) Partikulier_Listing_URLs::flush();
' >>"$LOG" 2>&1

# ------------------------------------------------------------------ 6. données
step "6/7 données de démonstration"
COUNT=$(wp post list --post_type=properties --format=count 2>/dev/null || echo 0)
if [ "$COUNT" -lt 30 ]; then
wp eval '
foreach(array("Casablanca","Rabat","Marrakech","Tanger","Agadir") as $t) wp_insert_term($t,"es_location");
foreach(array("Appartement","Maison","Terrain","Loft","Studio") as $t) wp_insert_term($t,"es_type");
foreach(array("À vendre","À louer") as $t) wp_insert_term($t,"es_category");
$d=array(
 array("Appartement lumineux 3 pièces","Casablanca","Appartement","À vendre",1280000,72,2),
 array("Maison contemporaine avec jardin","Rabat","Maison","À vendre",3750000,124,3),
 array("Appartement T2 vue mer avec balcon","Marrakech","Appartement","À louer",9500,52,1),
 array("Loft rénové en plein centre-ville","Tanger","Loft","À louer",12500,110,2),
 array("Maison de ville avec cour arborée","Agadir","Maison","À vendre",2850000,98,3),
 array("Studio calme proche des gares","Rabat","Studio","À vendre",850000,28,0));
// Duplication pour atteindre 30 annonces (pagination)
for($i=0; $i<4; $i++) $d = array_merge($d, $d);
$d = array_slice($d, 0, 30);
foreach($d as $x){ list($t,$c,$ty,$ca,$p,$a,$b)=$x;
 $id=wp_insert_post(array("post_type"=>"properties","post_status"=>"publish","post_title"=>$t,"post_author"=>1,
  "post_content"=>"Bien proposé directement par son propriétaire, sans commission d agence."));
 wp_set_object_terms($id,$c,"es_location"); wp_set_object_terms($id,$ty,"es_type"); wp_set_object_terms($id,$ca,"es_category");
 update_post_meta($id,"es_property_price",$p); update_post_meta($id,"es_property_area",$a);
 update_post_meta($id,"_pk_bedrooms_label",(string)$b); update_post_meta($id,"_pk_bathrooms_label","1");
 update_post_meta($id,"_pk_living_rooms_label","1"); update_post_meta($id,"_pk_status","actif");
 update_post_meta($id,"_pk_views",rand(20,300)); update_post_meta($id,"_pk_owner_role","proprietaire");
 update_post_meta($id,"_pk_city_name",$c);
 if (class_exists("Partikulier_Listing_URLs")) Partikulier_Listing_URLs::store_geo($id);
}' >>"$LOG" 2>&1

  # Images de démonstration : une photo source déclinée en 6 teintes.
  if [ -f "$ROOT/assets-demo/hero.jpg" ]; then
    mkdir -p /tmp/imgs && cp "$ROOT/assets-demo/hero.jpg" /tmp/imgs/h1.jpg
    php -r '$s="/tmp/imgs/h1.jpg";$t=[[30,10,-10],[-20,10,30],[10,30,-20],[40,-10,10],[-30,-10,20]];
    foreach($t as $i=>$c){$im=imagecreatefromjpeg($s);imagefilter($im,IMG_FILTER_COLORIZE,$c[0],$c[1],$c[2]);
    imagejpeg($im,"/tmp/imgs/h".($i+2).".jpg",85);}' 2>/dev/null
    IDS=$(wp post list --post_type=properties --format=ids 2>/dev/null); i=1
    for pid in $IDS; do
      G=""
      for k in 0 1 2; do
        n=$(( (pid + k - 1) % 6 + 1 )) # ancre sur l'ID : fixture identique d'une install a l'autre
        aid=$(wp media import /tmp/imgs/h$n.jpg --post_id=$pid --porcelain 2>/dev/null | tail -1)
        [ -n "$aid" ] && G="$G${G:+,}$aid"
        [ "$k" = "0" ] && wp post meta update $pid _thumbnail_id "$aid" >/dev/null 2>&1
      done
      wp post meta update $pid es_property_gallery "[$G]" --format=json >/dev/null 2>&1
      i=$((i+1))
    done
  else
    echo "  (assets-demo/hero.jpg absent : annonces sans photos)" | tee -a "$LOG"
  fi

  wp menu create "Principal" >/dev/null 2>&1
  wp menu item add-custom principal "Accueil" "$URL/" >/dev/null 2>&1
  wp menu item add-custom principal "Annonces" "$URL/annonces/" >/dev/null 2>&1
  wp menu location assign principal main >/dev/null 2>&1
fi

# Configuration Polylang (FR/EN/AR) - Appel après création des données
wp eval-file "$ROOT/scripts/provision-polylang.php" >>"$LOG" 2>&1
# Le premier passage initialise Polylang; le second verrouille l’état après ses migrations de première activation.
wp eval-file "$ROOT/scripts/provision-polylang.php" >>"$LOG" 2>&1
POLYLANG_JSON="$(wp option get polylang --format=json 2>/dev/null || echo '{}')"
[ "$(printf '%s' "$POLYLANG_JSON" | jq -r '.default_lang // empty')" = "fr" ] || { echo "Polylang default_lang absent" | tee -a "$LOG"; exit 1; }
[ "$(printf '%s' "$POLYLANG_JSON" | jq -r '.hide_default | tostring')" = "0" ] || { echo "Polylang hide_default doit être 0" | tee -a "$LOG"; exit 1; }
[ "$(printf '%s' "$POLYLANG_JSON" | jq -r '.browser | tostring')" = "1" ] || { echo "Polylang browser doit être 1" | tee -a "$LOG"; exit 1; }
echo "  Polylang options : $POLYLANG_JSON" >>"$LOG"

wp rewrite flush --hard >>"$LOG" 2>&1
rm -rf wp-content/uploads/partikulier-cache/* 2>/dev/null

# ------------------------------------------------------------------ 7. fin
step "7/7 terminé"
echo
  echo "  Annonces en base : $(wp post list --post_type=properties --format=count 2>/dev/null)"
  echo "  WordPress        : $(wp core version)"
  echo "  Estatik          : $(wp plugin get estatik --field=version)"
  echo "  Polylang         : $(wp plugin get polylang --field=version)"
  echo "  Query Monitor    : $(wp plugin get query-monitor --field=version)"
  echo "  WordPress dir    : $WP_DIR"
echo "  Journal          : $LOG"
echo
echo "  Démarrer le site :  bash scripts/start.sh"
echo "  Adresse          :  $URL"
  echo "  Administration   :  $URL/wp-admin  (identifiants fournis par PK_ADMIN_USER/PK_ADMIN_PASS)"
echo
