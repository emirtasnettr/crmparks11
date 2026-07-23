#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

echo "==> Git güncelleniyor..."
git pull origin main

echo "==> Composer bağımlılıkları..."
composer install --no-dev --optimize-autoloader

echo "==> Frontend derleniyor..."
# Önceki npm install denemeleri package-lock.json'u kirletebilir; repo sürümüne dön.
git checkout -- package-lock.json 2>/dev/null || true

if ! npm ci --no-audit --no-fund; then
  echo "==> npm ci uyumsuz, npm install ile devam ediliyor..."
  npm install --no-audit --no-fund
  # VPS'te lock kirli kalmasın; kalıcı düzeltme repodaki lock ile yapılır.
  git checkout -- package-lock.json 2>/dev/null || true
fi
npm run build

echo "==> Önbellek temizleniyor..."
php artisan optimize:clear

echo "==> Migration..."
php artisan migrate --force

echo "==> Cache yenileniyor..."
# .env yalnızca root'a açıksa config:cache şart; aksi halde www-data APP_KEY/DB göremez → 500.
if [[ -f .env ]]; then
  chown root:www-data .env 2>/dev/null || true
  chmod 640 .env 2>/dev/null || true
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Deploy root ile çalışırsa derlenen view/cache www-data tarafından yazılamaz → 500 (touch Utime).
if id www-data >/dev/null 2>&1; then
  echo "==> storage / bootstrap sahipliği düzeltiliyor (www-data)..."
  chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
  find storage bootstrap/cache -type d -exec chmod 775 {} + 2>/dev/null || true
  find storage bootstrap/cache -type f -exec chmod 664 {} + 2>/dev/null || true
fi

echo "==> Queue worker yeniden başlatılıyor (varsa)..."
php artisan queue:restart 2>/dev/null || true

echo "==> PHP-FPM yeniden yükleniyor (varsa)..."
if command -v systemctl >/dev/null 2>&1; then
  sudo systemctl reload php8.3-fpm 2>/dev/null || sudo systemctl reload php8.2-fpm 2>/dev/null || true
fi

echo "==> Deploy tamamlandı."
echo ""
echo "Ops (queue + scheduler):"
echo "  sudo bash deploy/install-ops.sh   # ilk kurulum / güncelleme"
echo "  bash deploy/verify-ops.sh         # durum kontrolü"
echo "Detay: DEPLOY.md Adım 12–13"
