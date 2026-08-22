#!/usr/bin/env bash
# R6 : bloque les libellés d’interface hors gettext via analyse de tokens PHP.
set -u
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
php "$ROOT/scripts/check-i18n-hardcoded.php"
