#!/bin/bash
# Démarre le site de développement en arrière-plan.
#   bash scripts/start.sh          → http://localhost:8090
#   PK_PORT=9000 bash scripts/start.sh
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WP_DIR="${PK_WP_DIR:-$ROOT/wp}"
PORT="${PK_PORT:-8090}"
SERVER_LOG="${PK_SERVER_LOG:-$WP_DIR/partikulier-server.log}"

[ -f "$WP_DIR/index.php" ] || { echo "WordPress absent. Lancez d'abord : bash scripts/install.sh"; exit 1; }

# MariaDB peut s'être arrêtée avec la session précédente.
pgrep -x mariadbd >/dev/null || (sudo mariadbd-safe --datadir=/var/lib/mysql --user=mysql >/dev/null 2>&1 &)
for i in $(seq 1 20); do sudo mariadb -e "SELECT 1" >/dev/null 2>&1 && break; sleep 1; done

if ss -ltn 2>/dev/null | grep -q ":$PORT "; then
  echo "Le port $PORT est déjà occupé — le site tourne probablement déjà."
  echo "http://localhost:$PORT"
  exit 0
fi

cd "$WP_DIR" || exit 1
nohup php -S 0.0.0.0:"$PORT" router.php >"$SERVER_LOG" 2>&1 &
sleep 2

if ss -ltn 2>/dev/null | grep -q ":$PORT "; then
  echo "Site démarré : http://localhost:$PORT"
  echo "Administration : http://localhost:$PORT/wp-admin  (identifiants fournis par PK_ADMIN_USER/PK_ADMIN_PASS)"
else
  echo "Échec du démarrage. Vérifiez que PHP est installé et que le port $PORT est libre."
  exit 1
fi
