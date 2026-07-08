#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Composer bağımlılıkları kuruluyor..."
composer install --no-dev --optimize-autoloader

if [[ ! -f public/build/manifest.json ]]; then
  echo "==> Frontend derleniyor..."
  npm ci
  npm run build
else
  echo "==> Frontend derlemesi mevcut, atlanıyor."
fi

echo "==> Kurulum sihirbazı başlatılıyor..."
php artisan crmlog:install "$@"
