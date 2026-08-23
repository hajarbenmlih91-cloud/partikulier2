#!/usr/bin/env bash
# Installation reproductible des outils de recette Partikulier.
# Usage: bash scripts/install-tooling.sh
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG="${PK_TOOLING_LOG:-$ROOT/documentation/install-tooling.log}"
SEMgrep_VERSION="1.132.0"
WPCLI_VERSION="2.12.0"
NODE_MAJOR="22"
mkdir -p "$(dirname "$LOG")"
exec > >(tee "$LOG") 2>&1

export DEBIAN_FRONTEND=noninteractive
sudo apt-get update -qq
sudo apt-get install -y -qq --no-install-recommends \
  php-cli php-mysql php-gd php-xml php-mbstring php-curl php-zip php-intl php-bcmath \
  mariadb-server mariadb-client curl unzip jq rsync git ca-certificates python3-venv \
  build-essential libnss3 libatk-bridge2.0-0 libdrm2 libxkbcommon0 libxcomposite1 \
  libxdamage1 libxfixes3 libxrandr2 libgbm1 libgtk-3-0

if ! command -v wp >/dev/null 2>&1 || ! wp --version 2>/dev/null | grep -q "WP-CLI $WPCLI_VERSION"; then
  curl -fsSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /tmp/wpcli.phar
  chmod +x /tmp/wpcli.phar
  sudo mv /tmp/wpcli.phar /usr/local/bin/wp
fi

if ! command -v semgrep >/dev/null 2>&1 || ! semgrep --version 2>/dev/null | grep -q "^$SEMgrep_VERSION$"; then
  sudo uv pip install --system "semgrep==$SEMgrep_VERSION"
fi

cd "$ROOT"
if [ ! -d node_modules ]; then
  npm install --no-audit --no-fund
fi
npx playwright install chromium

printf '\nTOOLING_COMPLETE=1\n'
printf 'PHP_VERSION=%s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'MARIADB_VERSION=%s\n' "$(mariadb --version | sed -E 's/.*Distrib ([0-9.]+).*/\1/')"
printf 'WPCLI_VERSION=%s\n' "$(wp --version | sed -n 's/^WP-CLI \([^ ]*\).*/\1/p')"
printf 'SEMGREP_VERSION=%s\n' "$(semgrep --version 2>/dev/null | tail -1)"
printf 'NODE_VERSION=%s\n' "$(node --version)"
printf 'PLAYWRIGHT_VERSION=%s\n' "$(npx playwright --version | sed -n 's/^Version //p')"
