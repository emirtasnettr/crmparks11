#!/usr/bin/env bash
# Verify CRMLog queue worker + scheduler cron on the VPS.
#   cd /var/www/crmlog && bash deploy/verify-ops.sh
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
FAILED=0

ok() { echo "  OK  $*"; }
bad() { echo "  FAIL $*"; FAILED=1; }

echo "==> Ops doğrulama ($APP_DIR)"

if [[ ! -f "$APP_DIR/artisan" ]]; then
  bad "artisan yok"
  exit 1
fi

if [[ -f "$APP_DIR/.env" ]]; then
  QUEUE_CONNECTION="$(grep -E '^QUEUE_CONNECTION=' "$APP_DIR/.env" | head -1 | cut -d= -f2- | tr -d '"' || true)"
  if [[ -z "$QUEUE_CONNECTION" ]]; then
    bad "QUEUE_CONNECTION .env içinde tanımlı değil"
  elif [[ "$QUEUE_CONNECTION" == "sync" ]]; then
    bad "QUEUE_CONNECTION=sync (job'lar arka planda çalışmaz; database önerilir)"
  else
    ok "QUEUE_CONNECTION=$QUEUE_CONNECTION"
  fi
else
  bad ".env bulunamadı"
fi

if command -v systemctl >/dev/null 2>&1; then
  if systemctl is-enabled crmlog-queue >/dev/null 2>&1; then
    ok "crmlog-queue enabled"
  else
    bad "crmlog-queue enabled değil"
  fi

  if systemctl is-active crmlog-queue >/dev/null 2>&1; then
    ok "crmlog-queue active"
  else
    bad "crmlog-queue active değil (systemctl status crmlog-queue)"
  fi
else
  bad "systemctl yok"
fi

if [[ -f /etc/cron.d/crmlog-scheduler ]]; then
  ok "cron dosyası: /etc/cron.d/crmlog-scheduler"
  if grep -q 'artisan schedule:run' /etc/cron.d/crmlog-scheduler; then
    ok "cron satırı schedule:run içeriyor"
  else
    bad "cron satırı schedule:run içermiyor"
  fi
else
  bad "cron dosyası yok (/etc/cron.d/crmlog-scheduler)"
fi

run_artisan() {
  if [[ "$(id -u)" -eq 0 ]]; then
    sudo -u www-data php "$APP_DIR/artisan" "$@"
  else
    php "$APP_DIR/artisan" "$@"
  fi
}

if run_artisan schedule:list >/tmp/crmlog-schedule-list.txt 2>/tmp/crmlog-schedule-list.err; then
  if grep -q 'crmlog:reminders:' /tmp/crmlog-schedule-list.txt; then
    ok "schedule:list hatırlatma komutlarını listeliyor"
    grep 'crmlog:reminders:' /tmp/crmlog-schedule-list.txt | sed 's/^/       /'
  else
    bad "schedule:list hatırlatma komutlarını göstermiyor"
    sed 's/^/       /' /tmp/crmlog-schedule-list.txt
  fi
else
  bad "schedule:list çalışmadı"
  sed 's/^/       /' /tmp/crmlog-schedule-list.err || true
fi

PENDING_JOBS="$(run_artisan tinker --execute="echo \\Illuminate\\Support\\Facades\\DB::table('jobs')->count();" 2>/dev/null || echo '?')"
FAILED_JOBS="$(run_artisan tinker --execute="echo \\Illuminate\\Support\\Facades\\DB::table('failed_jobs')->count();" 2>/dev/null || echo '?')"
ok "jobs tablosu bekleyen=$PENDING_JOBS failed=$FAILED_JOBS"

echo ""
if [[ "$FAILED" -eq 0 ]]; then
  echo "Sonuç: queue + scheduler hazır."
  exit 0
fi

echo "Sonuç: eksikler var. Kurulum için:"
echo "  sudo bash $APP_DIR/deploy/install-ops.sh"
echo "Detay: DEPLOY.md Adım 12–13"
exit 1
