#!/usr/bin/env bash
# Crée une sauvegarde SQL et fichiers avec manifest vérifiable.
set -Eeuo pipefail
OUT_DIR="${1:?Usage: backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass> }"
WP_DIR="${2:?Usage: backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass> }"
DB_NAME="${3:?Usage: backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass> }"
DB_USER="${4:?Usage: backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass> }"
DB_PASS="${5:?Usage: backup.sh <output-dir> <wp-dir> <db-name> <db-user> <db-pass> }"
case "$DB_NAME" in ''|*[!A-Za-z0-9_]*) echo 'DB_NAME invalide' >&2; exit 2;; esac
case "$DB_USER" in ''|*[!A-Za-z0-9_]*) echo 'DB_USER invalide' >&2; exit 2;; esac
[ -d "$WP_DIR" ] || { echo 'WP_DIR absent' >&2; exit 2; }
mkdir -p "$OUT_DIR"
chmod 700 "$OUT_DIR"
MYSQL_PWD="$DB_PASS" mariadb-dump --single-transaction --routines --triggers -u "$DB_USER" -h 127.0.0.1 "$DB_NAME" > "$OUT_DIR/database.sql"
tar --sort=name --mtime='UTC 2026-01-01' --owner=0 --group=0 --numeric-owner -czf "$OUT_DIR/wp-content.tar.gz" -C "$WP_DIR" wp-content
sha256sum "$OUT_DIR/database.sql" "$OUT_DIR/wp-content.tar.gz" > "$OUT_DIR/SHA256SUMS"
printf '{"format":"partikulier-backup-v1","created_at_utc":"%s","database":"%s","files":["database.sql","wp-content.tar.gz"],"sha256_manifest":"SHA256SUMS"}\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" "$DB_NAME" > "$OUT_DIR/manifest.json"
chmod 600 "$OUT_DIR"/*
printf 'BACKUP=PASS\nFILES=2\n'
