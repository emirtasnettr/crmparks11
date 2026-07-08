#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Git güncelleniyor..."
git pull origin main

echo "==> Composer bağımlılıkları..."
composer install --no-dev --optimize-autoloader

echo "==> Frontend derleniyor..."
if ! npm ci --no-audit --no-fund; then
  echo "==> npm ci uyumsuz, npm install ile devam ediliyor..."
  npm install --no-audit --no-fund
fi
npm run build

echo "==> Migration..."
php artisan migrate --force

echo "==> Cache yenileniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy tamamlandı."
