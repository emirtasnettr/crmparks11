#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Composer bağımlılıkları kuruluyor..."
composer install --no-dev --optimize-autoloader

if [[ ! -f public/build/manifest.json ]]; then
  echo "==> Frontend derleniyor..."
  if ! npm ci --no-audit --no-fund; then
    echo "==> npm ci uyumsuz, npm install ile devam ediliyor..."
    npm install --no-audit --no-fund
  fi
  npm run build
else
  echo "==> Frontend derlemesi mevcut, atlanıyor."
fi

echo "==> Kurulum sihirbazı başlatılıyor..."
php artisan crmlog:install "$@"
