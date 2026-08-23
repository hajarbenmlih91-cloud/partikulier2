#!/usr/bin/env bash
# Bascule atomiquement vers une release déjà présente et explicitement autorisée.
set -Eeuo pipefail
CURRENT_LINK="${1:?Usage: rollback.sh <current-link> <release-dir> <approved-file> }"
RELEASE_DIR="${2:?Usage: rollback.sh <current-link> <release-dir> <approved-file> }"
APPROVED_FILE="${3:?Usage: rollback.sh <current-link> <release-dir> <approved-file> }"
[ -f "$APPROVED_FILE" ] || { echo 'Approbation rollback absente' >&2; exit 2; }
[ -d "$RELEASE_DIR" ] || { echo 'Répertoire release absent' >&2; exit 2; }
[ "$(cat "$APPROVED_FILE")" = 'APPROVED' ] || { echo 'Approbation rollback invalide' >&2; exit 2; }
tmp="${CURRENT_LINK}.rollback.$$"
ln -sfn -- "$RELEASE_DIR" "$tmp"
mv -Tf -- "$tmp" "$CURRENT_LINK"
printf 'ROLLBACK=PASS\nTARGET=%s\n' "$RELEASE_DIR"
