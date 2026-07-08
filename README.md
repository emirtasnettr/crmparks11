# CRMLog — Kurye Operasyon Yönetim Sistemi

Profesyonel kurye operasyon yönetim platformu. İşletme, kurye, acente, finans ve hakediş yönetimini tek panelden yapın.

## Teknoloji

- PHP 8.3+ / Laravel 13
- MySQL (production) / SQLite (geliştirme)
- Tailwind CSS 4 + Alpine.js
- Spatie Laravel Permission
- Maatwebsite Excel

## Hızlı Kurulum (Geliştirme)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install
npm run build
php artisan serve
```

Tüm servisler (server + queue + logs + vite):

```bash
composer dev
```

## Production (VPS)

Detaylı kurulum adımları için: **[DEPLOY.md](DEPLOY.md)**

```bash
git clone https://github.com/emirtasnettr/crmparks11.git
cd crmparks11
composer install --no-dev --optimize-autoloader
cp .env.example .env
# .env dosyasını düzenleyin (APP_DEBUG=false, MySQL, ADMIN_INITIAL_PASSWORD)
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
npm ci && npm run build
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Güncellemeler için: `./deploy.sh`

## Demo Hesaplar (local/testing)

| Rol | E-posta | Şifre |
|-----|---------|-------|
| Süper Admin | admin@crmlog.com | password |
| Genel Müdür | mudur@crmlog.com | password |
| Operasyon Yöneticisi | operasyon@crmlog.com | password |

> Production ortamında `ADMIN_INITIAL_PASSWORD` ile güçlü şifre tanımlayın.

## Modüller

- Dashboard, İşletmeler, Kuryeler, Acenteler
- Finans (cari, gelir/gider, fatura, kârlılık)
- Form Builder, Landing Page Builder
- Kullanıcı / rol / yetki yönetimi
- Excel dışa aktarım

## API

```
GET /api/v1/health
```

## Testler

```bash
php artisan test
```
