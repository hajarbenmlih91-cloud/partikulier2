#!/usr/bin/env bash
# Détruit l'environnement de test du sandbox. Ne touche jamais au dépôt ni au workspace.
#   bash scripts/arena/teardown-sandbox.sh            # vide le workspace runtime, garde les templates (warm <15 s au prochain run)
#   bash scripts/arena/teardown-sandbox.sh --purge    # supprime AUSSI les templates (prochain run = cold)
set -uo pipefail
PK=/opt/pk
PORT="${PK_PORT:-8090}"
keep_templates=1
[ "${1:-}" = "--purge" ] && keep_templates=0

# 1. processus
if [ -f "$PK/run/php.pid" ]; then kill "$(cat "$PK/run/php.pid")" >/dev/null 2>&1; fi
for pid in $(ps -eo pid,comm,args | awk -v p="$PORT" '$2=="php" && $0 ~ ("php -S .*:" p " router.php") {print $1}'); do kill "$pid" >/dev/null 2>&1; done
pkill -f "php-fpm.*partikulier-reference" >/dev/null 2>&1
pkill -f nginx >/dev/null 2>&1
# 2. base
if sudo -n test -e /run/mysqld/mysqld.sock 2>/dev/null; then
  for d in $(sudo -n mariadb -e "SHOW DATABASES;" 2>/dev/null | grep -E '^pk_' ); do
    sudo -n mariadb -e "DROP DATABASE IF EXISTS \`$d\`;" 2>/dev/null
  done
fi
sudo -n mariadb -e "SHUTDOWN" >/dev/null 2>&1
sleep 1
pgrep -x mariadbd >/dev/null 2>&1 && sudo -n pkill -x mariadbd
# 3. fichiers runtime
sudo -n rm -rf /var/lib/mysql /run/mysqld /opt/pk/wp /opt/pk/reference /opt/pk/run 2>/dev/null
rm -rf "$PK/wp" "$PK/run" "$PK/reference" 2>/dev/null
rm -f "$PK/wp" "$PK"/browsers/__none 2>/dev/null
# 4. espace
rm -f /opt/pk-server.log /opt/pk-mariadb.log /opt/pk-install.log /opt/pk-apt.log 2>/dev/null
if [ $keep_templates -eq 0 ]; then
  rm -rf "$PK/templates"
  echo "TEARDOWN=purge (prochain bootstrap = cold)"
else
  echo "TEARDOWN=soft (templates conservés pour warm <15 s)"
fi
# 5. auto-contrôle : rien ne doit rester vivant ni résider dans le workspace
left_procs=0
for prog in mariadbd nginx php-fpm; do left_procs=$((left_procs + $(pgrep -x "$prog" | wc -l))); done
left_procs=$((left_procs + $(ps -eo pid,args | awk -v p="$PORT" '$0 ~ ("php -S .*:" p " router.php")' | grep -vc awk)))
ws_size=$(du -sm "$HOME" 2>/dev/null | cut -f1)
repo_clean=$(git -C "$PK/repo" status --porcelain 2>/dev/null | grep -v '^?? node_modules' | wc -l)
printf 'processus restants : %s\n' "$left_procs"
printf 'taille workspace    : %s Mo\n' "$ws_size"
printf 'dépôt modifié       : %s ligne(s)\n' "$repo_clean"
[ "$left_procs" = "0" ] && echo "TEARDOWN_OK=1" || echo "TEARDOWN_OK=0"
