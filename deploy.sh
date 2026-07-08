#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Git güncelleniyor..."
git pull origin main

echo "==> Composer bağımlılıkları..."
composer install --no-dev --optimize-autoloader

echo "==> Frontend derleniyor..."
npm ci
npm run build

echo "==> Migration..."
php artisan migrate --force

echo "==> Cache yenileniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Deploy tamamlandı."
