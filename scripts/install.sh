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
set -u

# Racine du paquet (le dossier qui contient ce script/..)
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
THEME_SRC="$ROOT/theme"
WP_DIR="${PK_WP_DIR:-$ROOT/wp}"
PORT="${PK_PORT:-8090}"
URL="http://localhost:$PORT"
LOG="$ROOT/install.log"

export DEBIAN_FRONTEND=noninteractive
echo "=== $(date) ===" > "$LOG"
step(){ echo ">>> $1" | tee -a "$LOG"; }
have(){ command -v "$1" >/dev/null 2>&1; }

if [ ! -d "$THEME_SRC" ]; then
  echo "ERREUR : $THEME_SRC est introuvable."
  echo "Dézippez partikulier-x.y.z.zip dans un dossier 'theme/' à la racine du paquet."
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
for i in $(seq 1 30); do sudo mariadb -e "SELECT 1" >/dev/null 2>&1 && break; sleep 1; done

sudo mariadb -e "CREATE DATABASE IF NOT EXISTS wp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'wp'@'localhost' IDENTIFIED BY 'wp';
CREATE USER IF NOT EXISTS 'wp'@'127.0.0.1' IDENTIFIED BY 'wp';
GRANT ALL ON wp.* TO 'wp'@'localhost'; GRANT ALL ON wp.* TO 'wp'@'127.0.0.1';
FLUSH PRIVILEGES;" >>"$LOG" 2>&1

# ------------------------------------------------------------------ 3. wp-cli
step "3/7 WP-CLI"
if ! have wp; then
  curl -sL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wpcli
  chmod +x /tmp/wpcli && sudo mv /tmp/wpcli /usr/local/bin/wp
fi

# ------------------------------------------------------------------ 4. coeur WP
step "4/7 WordPress"
mkdir -p "$WP_DIR" && cd "$WP_DIR" || exit 1
[ -f wp-settings.php ] || wp core download --locale=fr_FR --force >>"$LOG" 2>&1
if [ ! -f wp-config.php ]; then
  wp config create --dbname=wp --dbuser=wp --dbpass=wp --dbhost=127.0.0.1 \
    --locale=fr_FR --skip-check >>"$LOG" 2>&1
  wp config set WP_DEBUG true --raw >>"$LOG" 2>&1
  wp config set WP_DEBUG_LOG true --raw >>"$LOG" 2>&1
  wp config set WP_DEBUG_DISPLAY false --raw >>"$LOG" 2>&1
fi
if ! wp core is-installed >/dev/null 2>&1; then
  wp core install --url="$URL" --title="Partikulier" \
    --admin_user=admin --admin_password=admin --admin_email=a@b.co --skip-email >>"$LOG" 2>&1
  wp language core install fr_FR >>"$LOG" 2>&1
  wp site switch-language fr_FR >>"$LOG" 2>&1
  wp rewrite structure '/%postname%/' --hard >>"$LOG" 2>&1
fi

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
[ -d wp-content/plugins/estatik ] || wp plugin install estatik >>"$LOG" 2>&1
wp plugin activate estatik >>"$LOG" 2>&1
wp theme activate partikulier >>"$LOG" 2>&1

# Réglages sans lesquels le formulaire refuse tous les dépôts.
wp eval '
$o = get_option("pk_theme_options", array());
if (empty($o["whatsapp_validation_number"])) $o["whatsapp_validation_number"] = "212612345678";
if (empty($o["automation_api_secret"]))      $o["automation_api_secret"]      = "secret-dev-local";
update_option("pk_theme_options", $o);
do_action("after_switch_theme");
if (class_exists("Partikulier_Listing_URLs")) Partikulier_Listing_URLs::flush();
' >>"$LOG" 2>&1

# ------------------------------------------------------------------ 6. données
step "6/7 données de démonstration"
COUNT=$(wp post list --post_type=properties --format=count 2>/dev/null || echo 0)
if [ "$COUNT" -lt 6 ]; then
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
        n=$(( (i+k-1) % 6 + 1 ))
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

wp rewrite flush --hard >>"$LOG" 2>&1
rm -rf wp-content/uploads/partikulier-cache/* 2>/dev/null

# ------------------------------------------------------------------ 7. fin
step "7/7 terminé"
echo
echo "  Annonces en base : $(wp post list --post_type=properties --format=count 2>/dev/null)"
echo "  WordPress        : $WP_DIR"
echo "  Journal          : $LOG"
echo
echo "  Démarrer le site :  bash scripts/start.sh"
echo "  Adresse          :  $URL"
echo "  Administration   :  $URL/wp-admin  (admin / admin)"
echo
