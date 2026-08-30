#!/usr/bin/env bash
# =============================================================================
# Partikulier — sandbox de qualification. Installe l'env réel de test.
#   bash scripts/arena/bootstrap-sandbox.sh            # warm : < 15 s visé
#   bash scripts/arena/bootstrap-sandbox.sh --cold     # reconstruit app + dump SQL
#   bash scripts/arena/bootstrap-sandbox.sh --tier=L3  # + navigateurs (gates visuels)
#   bash scripts/arena/bootstrap-sandbox.sh --mode=reference  # nginx+php-fpm (capacity)
# Rien n'est écrit dans le dépôt. Tout est sous /opt/pk, détruit par teardown-sandbox.sh.
# =============================================================================
set -uo pipefail
PK=/opt/pk; PKLOG=/tmp/pk-logs; TPL=$PK/templates; RUN=$PK/run
PORT="${PK_PORT:-8090}"; WP_VERSION="${PK_WP_VERSION:-7.1}"
REPO_URL="${PK_REPO_URL:-https://github.com/hajarbenmlih91-cloud/partikulier2.git}"
BRANCH="${PK_BRANCH:-automation/capacity-apcu-a58942c}"
# Garde d'adresse : le dépôt hajarbenmlih91-cloud/partikulier (sans le "2") est
# l'archive de passation figee du 2026-08-20 (3 commits, 0 workflow). Y cloner
# ferait verifier du code qui ne tourne nulle part.
case "$REPO_URL" in
  */partikulier2.git|*/partikulier2) : ;;
  *) echo "FATAL: PK_REPO_URL doit pointer sur .../partikulier2.git (recu: $REPO_URL)" >&2; exit 2 ;;
esac
T0=$(date +%s%N)
COLD=0; TIER=L1; MODE=dev; NEED_BR=0
for a in "$@"; do case "$a" in
  --cold) COLD=1 ;; --tier=L2) TIER=L2 ;; --tier=L3) NEED_BR=1 ;;
  --mode=reference) MODE=reference ;; *) echo "option inconnue: $a" >&2; exit 2 ;;
esac; done
mkdir -p "$PKLOG" "$RUN" "$PK"; say(){ printf '%-28s %s\n' "$1" "$2"; }
dur(){ local d=$(( $(date +%s%N) - $2 )); printf '%-28s %6.2f s\n' "$1" "$(awk -v n="$d" 'BEGIN{printf "%.2f", n/1e9}')"; }
fail(){ echo "ÉCHEC: $1" >&2; exit "${2:-1}"; }
db_up(){ sudo -n mariadb -e "SELECT 1" >/dev/null 2>/dev/null; }
db_stop(){ sudo -n mariadb -e "SHUTDOWN" >/dev/null 2>&1
  for _ in $(seq 1 25); do pgrep -x mariadbd >/dev/null 2>&1 || return 0; sleep .2; done
  sudo -n pkill -x mariadbd >/dev/null 2>&1; sleep 1; }

# ---------- 0. outils ----------
miss=()
command -v php >/dev/null || miss+=(php8.4-cli php8.4-mysql php8.4-gd php8.4-xml php8.4-mbstring php8.4-curl php8.4-zip php8.4-intl)
command -v mariadbd >/dev/null || miss+=(mariadb-server mariadb-client)
command -v jq >/dev/null || miss+=(jq)
command -v unzip >/dev/null || miss+=(unzip)
if [ ${#miss[@]} -gt 0 ]; then
  command -v apt-get >/dev/null || fail "apt-get absent, paquets requis: ${miss[*]}" 75
  sudo -n apt-get update -qq >/dev/null || fail "apt update impossible (besoin de sudo)" 75
  sudo -n DEBIAN_FRONTEND=noninteractive apt-get install -y -qq --no-install-recommends "${miss[@]}" >"$PKLOG/apt.log" 2>&1 \
    || fail "apt install: voir $PKLOG/apt.log" 75
fi
command -v wp >/dev/null || { curl -fsSL "https://github.com/wp-cli/wp-cli/releases/download/v2.12.0/wp-cli-2.12.0.phar" -o "$PKLOG/wp.phar" \
  && sudo -n cp "$PKLOG/wp.phar" /usr/local/bin/wp && sudo -n chmod 755 /usr/local/bin/wp; } || fail "wp-cli absent" 75
if [ $NEED_BR -eq 1 ]; then
  if ! ls -d "$PK"/browsers/chromium* >/dev/null 2>&1; then
    [ -d "$PK/repo" ] || git clone -q "$REPO_URL" "$PK/repo" || fail "clone pour navigateurs" 75
    [ -d "$PK/repo/node_modules" ] || ( cd "$PK/repo" && npm ci --no-audit --no-fund >"$PKLOG/npm.log" 2>&1 )
    ( cd "$PK/repo" && PLAYWRIGHT_BROWSERS_PATH="$PK/browsers" npx --no-install playwright install chromium ) >"$PKLOG/pw.log" 2>&1
  fi
  ls -d "$PK"/browsers/chromium* >/dev/null 2>&1 || fail "navigateurs indisponibles (tier L3), voir $PKLOG/pw.log" 75
fi

# ---------- 1. dépôt (jamais modifié) ----------
t=$(date +%s%N)
if [ ! -d "$PK/repo/.git" ]; then mkdir -p "$PK"; git clone -q "$REPO_URL" "$PK/repo" || fail "clone impossible" 75; fi
git -C "$PK/repo" checkout -q "$BRANCH" 2>/dev/null || git -C "$PK/repo" checkout -q --detach "origin/$BRANCH" || fail "branche $BRANCH" 75
SHA=$(git -C "$PK/repo" rev-parse HEAD); dur "repo ${SHA:0:7}" "$t"

# ---------- 2. base de données (toujours régénérée, jamais de snapshot à chaud) ----------
t=$(date +%s%N)
db_stop
sudo -n rm -rf /var/lib/mysql || fail "rm datadir (besoin de sudo)" 75
sudo -n mkdir -p /var/lib/mysql /run/mysqld && sudo -n chown -R mysql:mysql /var/lib/mysql /run/mysqld
sudo -n mariadb-install-db --user=mysql --datadir=/var/lib/mysql >"$PKLOG/db-init.log" 2>&1 </dev/null || fail "mariadb-install-db" 1
( sudo -n setsid nohup mariadbd-safe --datadir=/var/lib/mysql --user=mysql >"$PKLOG/mariadb.log" 2>&1 < /dev/null & )
for _ in $(seq 1 60); do db_up && break; sleep .25; done; db_up || fail "MariaDB ne répond pas" 75
sudo -n mariadb -e "CREATE DATABASE IF NOT EXISTS pk_sb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'pk'@'localhost' IDENTIFIED BY 'pk123';
  GRANT ALL ON pk_sb.* TO 'pk'@'localhost'; FLUSH PRIVILEGES;" || fail "création base" 1
dur "db" "$t"

# ---------- 3. application : template warm, sinon cold ----------
t=$(date +%s%N)
if [ $COLD -eq 0 ] && [ -f "$TPL/env-wp.tar.gz" ] && [ -f "$TPL/db.sql.gz" ]; then
  rm -rf "$PK/wp"; mkdir -p "$PK/wp"
  tar xzf "$TPL/env-wp.tar.gz" -C "$PK/wp" || fail "restore app" 1
  gunzip -c "$TPL/db.sql.gz" | sudo -n mariadb pk_sb || fail "restore dump" 1
  ENVMODE=warm
else
  ENVMODE=cold
  rm -rf "$PK/wp"; mkdir -p "$PK/wp" "$TPL"
  [ -f "$TPL/wordpress-$WP_VERSION.zip" ] || curl -fsSL -o "$TPL/wordpress-$WP_VERSION.zip" \
    "https://downloads.wordpress.org/release/wordpress-$WP_VERSION.zip" || fail "download WP" 75
  ( cd "$PK/wp" && unzip -q "$TPL/wordpress-$WP_VERSION.zip" && shopt -s dotglob && mv wordpress/* . && rmdir wordpress ) || fail "unpack WP" 1
  ( cd "$PK/repo" && WP_CLI_ALLOW_ROOT=1 PK_WP_DIR="$PK/wp" PK_DB_NAME=pk_sb PK_DB_USER=pk PK_DB_PASS=pk123 \
      PK_ADMIN_USER=admin PK_ADMIN_PASS=sandbox-only PK_ADMIN_EMAIL=s@b.test PK_PORT=$PORT \
      bash scripts/install.sh ) >"$PKLOG/install.log" 2>&1 || fail "install.sh (voir $PKLOG/install.log)" 1
  tar czf "$TPL/env-wp.tar.gz" -C "$PK/wp" . || fail "tar app" 1
  sudo -n mariadb --databases pk_sb --skip-comments pk_sb 2>/dev/null | gzip > "$TPL/db.sql.gz" \
    || ( sudo -n mysqldump --single-transaction --databases pk_sb 2>/dev/null | gzip > "$TPL/db.sql.gz" )
  { echo "WP_VERSION=$WP_VERSION"; echo "SHA=$SHA"; echo "BUILT=$(date -u +%FT%TZ)";
    echo "LISTINGS=$(wp --path=$PK/wp post list --post_type=properties --format=count --allow-root --skip-plugins 2>/dev/null)";
    echo "ATTACHMENTS=$(wp --path=$PK/wp post list --post_type=attachment --format=count --allow-root --skip-plugins 2>/dev/null)";
    echo "MEDIA_SHA=$(find $PK/wp/wp-content/uploads -name '*.jpg' -exec sha256sum {} + 2>/dev/null | awk '{print $1}' | sort | sha256sum | cut -c1-16)"; } > "$TPL/template-manifest.json"
fi
dur "app ($ENVMODE)" "$t"

# ---------- 4. HTTP ----------
t=$(date +%s%N)
if [ "$MODE" = reference ]; then
  ( cd "$PK/repo" && PK_WP_DIR="$PK/wp" PK_PORT=$PORT PK_PHP_WORKERS="${PK_PHP_WORKERS:-2}" \
      PK_REFERENCE_RUN_DIR=$PK/reference bash scripts/start-reference-web.sh ) >"$PKLOG/web.log" 2>&1 \
    || { say "mode reference" "INDISPONIBLE → gates capacity en exit 75"; MODE=dev; }
fi
if [ "$MODE" = dev ]; then
  if [ -f "$RUN/php.pid" ]; then kill "$(cat "$RUN/php.pid")" >/dev/null 2>&1; fi
  for pid in $(ps -eo pid,args | awk -v p="$PORT" '$0 ~ ("php .*0\\.0\\.0\\.0:" p) {print $1}'); do kill "$pid" >/dev/null 2>&1; done
  sleep .2
  ( cd "$PK/wp" && setsid nohup php -S 0.0.0.0:$PORT router.php > "$PKLOG/server.log" 2>&1 < /dev/null & echo $! > "$RUN/php.pid" )
fi
for _ in $(seq 1 60); do s=$(curl -s -o /dev/null -w '%{http_code}' --max-time 3 "http://localhost:$PORT/ar/" 2>/dev/null); [ "$s" = "200" ] && break; sleep .25; done
dur "http" "$t"
[ "${s:-000}" = "200" ] || fail "le site ne répond pas sur /ar/ (status=$s), voir $PKLOG/server.log et $PKLOG/mariadb.log" 1

# ---------- 5. contrat de sortie ----------
cat > "$RUN/bootstrap.env" <<ENV
PK_WP_DIR=$PK/wp
PK_REPO=$PK/repo
PK_BASE=http://localhost:$PORT
PK_PORT=$PORT
PK_SHA=$SHA
PK_ENV_MODE=$ENVMODE
PK_SERVER_MODE=$MODE
PK_TIER=$TIER
PK_LOG_DIR=$PKLOG
ENV
say "URL" "http://localhost:$PORT ($s sur /ar/)"
say "annonces" "$(wp --path=$PK/wp post list --post_type=properties --format=count --allow-root --skip-plugins 2>/dev/null)"
say "médias" "$(wp --path=$PK/wp post list --post_type=attachment --format=count --allow-root --skip-plugins 2>/dev/null)"
say "thème" "$(wp --path=$PK/wp theme list --status=active --field=name --allow-root --skip-plugins 2>/dev/null)"
say "media_hashes" "$(grep -h MEDIA_SHA "$TPL/template-manifest.json" 2>/dev/null)"
TOTAL=$(awk -v a=$(date +%s%N) -v b=$T0 'BEGIN{printf "%.2f", (a-b)/1e9}'); printf '%-28s %6.2f s\n' "TOTAL" "$TOTAL"
if [ "$ENVMODE" = warm ]; then
  tot=$(awk -v a=$(date +%s%N) -v b=$T0 'BEGIN{printf "%.2f", (a-b)/1e9}')
  ok=$(awk -v t="$tot" 'BEGIN{print (t<15)?"PASS":"FAIL"}')
  say "A1 warm < 15 s" "$ok  (${tot} s)"
  [ "$ok" = PASS ] || exit 1
fi
