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

echo "==> Önbellek temizleniyor..."
php artisan optimize:clear

echo "==> Migration..."
php artisan migrate --force

echo "==> Cache yenileniyor..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Queue worker yeniden başlatılıyor (varsa)..."
php artisan queue:restart 2>/dev/null || true

echo "==> PHP-FPM yeniden yükleniyor (varsa)..."
if command -v systemctl >/dev/null 2>&1; then
  sudo systemctl reload php8.3-fpm 2>/dev/null || sudo systemctl reload php8.2-fpm 2>/dev/null || true
fi

echo "==> Deploy tamamlandı."
