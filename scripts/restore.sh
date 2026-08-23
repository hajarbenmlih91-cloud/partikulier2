#!/usr/bin/env bash
# Restaure une sauvegarde après vérification intégrale ; ne supprime jamais la source.
set -Eeuo pipefail
BACKUP_DIR="${1:?Usage: restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir> }"
DB_NAME="${2:?Usage: restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir> }"
DB_USER="${3:?Usage: restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir> }"
DB_PASS="${4:?Usage: restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir> }"
WP_DIR="${5:?Usage: restore.sh <backup-dir> <db-name> <db-user> <db-pass> <wp-dir> }"
case "$DB_NAME" in ''|*[!A-Za-z0-9_]*) echo 'DB_NAME invalide' >&2; exit 2;; esac
case "$DB_USER" in ''|*[!A-Za-z0-9_]*) echo 'DB_USER invalide' >&2; exit 2;; esac
[ -f "$BACKUP_DIR/SHA256SUMS" ] || { echo 'Manifest SHA absent' >&2; exit 2; }
( cd "$BACKUP_DIR" && sha256sum --check --strict SHA256SUMS )
MYSQL_PWD="$DB_PASS" mariadb -u "$DB_USER" -h 127.0.0.1 "$DB_NAME" < "$BACKUP_DIR/database.sql"
tmp=$(mktemp -d)
trap 'rm -rf "$tmp"' EXIT
tar -xzf "$BACKUP_DIR/wp-content.tar.gz" -C "$tmp"
mkdir -p "$WP_DIR"
cp -a "$tmp/wp-content/." "$WP_DIR/wp-content/"
[ -f "$WP_DIR/wp-load.php" ] && wp --path="$WP_DIR" core is-installed --allow-root >/dev/null
printf 'RESTORE=PASS\n'
