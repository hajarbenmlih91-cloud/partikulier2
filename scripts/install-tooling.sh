#!/usr/bin/env bash
# Installation reproductible des outils de recette Partikulier.
# Usage: bash scripts/install-tooling.sh
set -Eeuo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOG="${PK_TOOLING_LOG:-$ROOT/documentation/install-tooling.log}"
SEMgrep_VERSION="1.132.0"
WPCLI_VERSION="2.12.0"
WPCLI_SHA512="be928f6b8ca1e8dfb9d2f4b75a13aa4aee0896f8a9a0a1c45cd5d2c98605e6172e6d014dda2e27f88c98befc16c040cbb2bd1bfa121510ea5cdf5f6a30fe8832"
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
  curl -fsSL "https://github.com/wp-cli/wp-cli/releases/download/v${WPCLI_VERSION}/wp-cli-${WPCLI_VERSION}.phar" -o /tmp/wpcli.phar
  printf '%s  %s\n' "$WPCLI_SHA512" /tmp/wpcli.phar | sha512sum -c -
  chmod +x /tmp/wpcli.phar
  sudo mv /tmp/wpcli.phar /usr/local/bin/wp
fi

if ! command -v semgrep >/dev/null 2>&1 || ! semgrep --version 2>/dev/null | grep -q "^$SEMgrep_VERSION$"; then
  sudo uv pip install --system "semgrep==$SEMgrep_VERSION"
fi

cd "$ROOT"
if ! command -v node >/dev/null 2>&1; then
  printf 'Node.js %s is required but node is not installed.\n' "$NODE_MAJOR" >&2
  exit 2
fi
node_major="$(node -p 'process.versions.node.split(".")[0]')"
if [ "$node_major" != "$NODE_MAJOR" ]; then
  printf 'Node.js major %s required; found %s.\n' "$NODE_MAJOR" "$node_major" >&2
  exit 2
fi
npm ci --no-audit --no-fund
npx --no-install playwright install chromium

printf '\nTOOLING_COMPLETE=1\n'
printf 'PHP_VERSION=%s\n' "$(php -r 'echo PHP_VERSION;')"
printf 'MARIADB_VERSION=%s\n' "$(mariadb --version | sed -E 's/.*Distrib ([0-9.]+).*/\1/')"
printf 'WPCLI_VERSION=%s\n' "$(wp --version | sed -n 's/^WP-CLI \([^ ]*\).*/\1/p')"
printf 'SEMGREP_VERSION=%s\n' "$(semgrep --version 2>/dev/null | tail -1)"
printf 'NODE_VERSION=%s\n' "$(node --version)"
printf 'PLAYWRIGHT_VERSION=%s\n' "$(npx --no-install playwright --version | sed -n 's/^Version //p')"
