#!/usr/bin/env bash
# Install CRMLog queue worker + Laravel scheduler cron.
# Run on the VPS as root from the app directory:
#   cd /var/www/crmlog && bash deploy/install-ops.sh
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SERVICE_SRC="$APP_DIR/deploy/crmlog-queue.service"
SERVICE_DST="/etc/systemd/system/crmlog-queue.service"
CRON_SRC="$APP_DIR/deploy/crmlog-scheduler.cron"
CRON_DST="/etc/cron.d/crmlog-scheduler"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Bu script root olarak çalıştırılmalı (sudo bash deploy/install-ops.sh)."
  exit 1
fi

if [[ ! -f "$APP_DIR/artisan" ]]; then
  echo "artisan bulunamadı: $APP_DIR"
  exit 1
fi

echo "==> Queue systemd birimi kuruluyor..."
install -m 644 "$SERVICE_SRC" "$SERVICE_DST"
systemctl daemon-reload
systemctl enable --now crmlog-queue

echo "==> Scheduler cron kuruluyor..."
install -m 644 "$CRON_SRC" "$CRON_DST"

echo "==> Queue worker yeniden başlatılıyor..."
sudo -u www-data php "$APP_DIR/artisan" queue:restart 2>/dev/null || true

echo ""
bash "$APP_DIR/deploy/verify-ops.sh"
