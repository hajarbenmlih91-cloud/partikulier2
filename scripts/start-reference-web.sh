#!/usr/bin/env bash
# Start the disposable CDC reference web stack: Nginx + PHP-FPM static pool.
set -Eeuo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${PK_WP_DIR:?PK_WP_DIR is required}"
PORT="${PK_PORT:-8090}"
WORKERS="${PK_PHP_WORKERS:-4}"
RUN_DIR="${PK_REFERENCE_RUN_DIR:-${RUNNER_TEMP:-/tmp}/partikulier-reference-${PORT}}"
CGROUP_ROOT="${PK_CGROUP_ROOT:-/sys/fs/cgroup}"
CGROUP_PATH="$CGROUP_ROOT/partikulier-reference-${PORT}-$$"
SOCKET="$RUN_DIR/php-fpm.sock"
NGINX_LOG="${PK_SERVER_LOG:-$RUN_DIR/nginx-error.log}"

[ -f "$WP_DIR/index.php" ] || { echo "WordPress absent: $WP_DIR" >&2; exit 1; }
command -v nginx >/dev/null || { echo 'nginx absent' >&2; exit 1; }
FPM_BIN="$(command -v php-fpm8.3 || command -v php-fpm || true)"
[ -n "$FPM_BIN" ] || { echo 'php-fpm absent' >&2; exit 1; }
mkdir -p "$RUN_DIR" "$RUN_DIR/nginx" "$RUN_DIR/client_body" "$RUN_DIR/proxy" "$RUN_DIR/fastcgi" "$RUN_DIR/uwsgi" "$RUN_DIR/scgi"
if ! sudo -n mkdir "$CGROUP_PATH" 2>/dev/null; then
  echo "Impossible de créer le cgroup de mesure: $CGROUP_PATH" >&2
  exit 1
fi
for controller in memory cpu; do
  [ -e "$CGROUP_PATH/${controller}.stat" ] || { echo "Contrôleur cgroup absent: $CGROUP_PATH/${controller}.stat" >&2; exit 1; }
done
printf '%s\n' "$CGROUP_PATH" > "$RUN_DIR/cgroup.path"
rm -f "$SOCKET" "$RUN_DIR/nginx.pid" "$RUN_DIR/php-fpm.pid"

cat > "$RUN_DIR/php-fpm.conf" <<EOF
[global]
daemonize = no
pid = $RUN_DIR/php-fpm.pid
error_log = $RUN_DIR/php-fpm-error.log

[www]
user = www-data
group = www-data
listen = $SOCKET
listen.owner = www-data
listen.group = www-data
pm = static
pm.max_children = $WORKERS
clear_env = no
catch_workers_output = yes
php_admin_value[error_log] = $RUN_DIR/php-error.log
php_admin_flag[log_errors] = on
php_admin_value[opcache.enable] = 1
php_admin_value[opcache.memory_consumption] = 128
php_admin_value[opcache.validate_timestamps] = 0
EOF

cat > "$RUN_DIR/nginx.conf" <<EOF
user www-data;
pid $RUN_DIR/nginx.pid;
error_log $NGINX_LOG warn;
worker_processes 1;
events { worker_connections 2048; }
http {
  access_log $RUN_DIR/nginx-access.log;
  include /etc/nginx/mime.types;
  default_type application/octet-stream;
  sendfile on;
  client_body_temp_path $RUN_DIR/client_body;
  proxy_temp_path $RUN_DIR/proxy;
  fastcgi_temp_path $RUN_DIR/fastcgi;
  uwsgi_temp_path $RUN_DIR/uwsgi;
  scgi_temp_path $RUN_DIR/scgi;
  server {
    listen $PORT;
    server_name _;
    root $WP_DIR;
    index index.php;
    client_max_body_size 32m;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location ~ \.php$ {
      include /etc/nginx/fastcgi_params;
      fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
      fastcgi_param HTTP_AUTHORIZATION \$http_authorization;
      fastcgi_param HTTP_PROXY '';
      fastcgi_pass unix:$SOCKET;
    }
  }
}
EOF

sudo -n bash -c 'group="$1"; shift; echo "$BASHPID" > "$group/cgroup.procs"; exec "$@"' _ "$CGROUP_PATH" "$FPM_BIN" -y "$RUN_DIR/php-fpm.conf" >"$RUN_DIR/php-fpm.log" 2>&1 &
FPM_PID=$!
for _ in $(seq 1 40); do [ -S "$SOCKET" ] && break; kill -0 "$FPM_PID" 2>/dev/null || { cat "$RUN_DIR/php-fpm.log" >&2; exit 1; }; sleep .25; done
[ -S "$SOCKET" ] || { cat "$RUN_DIR/php-fpm.log" >&2; exit 1; }
sudo -n bash -c 'group="$1"; shift; echo "$BASHPID" > "$group/cgroup.procs"; exec "$@"' _ "$CGROUP_PATH" nginx -c "$RUN_DIR/nginx.conf" -p "$RUN_DIR/nginx" -g 'daemon off;' >"$RUN_DIR/nginx-stdout.log" 2>&1 &
NGINX_PID=$!
for _ in $(seq 1 30); do ss -ltn 2>/dev/null | grep -q ":$PORT " && break; sleep .25; done
ss -ltn 2>/dev/null | grep -q ":$PORT " || { cat "$NGINX_LOG" >&2; exit 1; }
printf 'REFERENCE_WEB_STARTED=1\nSERVER_MODE=nginx-php-fpm\nPORT=%s\nPHP_FPM_WORKERS=%s\nRUN_DIR=%s\nCGROUP_PATH=%s\nFPM_PID=%s\nNGINX_PID=%s\n' "$PORT" "$WORKERS" "$RUN_DIR" "$CGROUP_PATH" "$FPM_PID" "$NGINX_PID"
