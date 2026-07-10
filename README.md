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
git clone https://github.com/emirtasnettr/crmparks11.git crmlog
cd crmlog
chmod +x install.sh
./install.sh
```

Sihirbaz sizi APP_URL, veritabanı ve admin şifresi için yönlendirir. Güncellemeler için: `./deploy.sh`

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
GET  /api/v1/health
POST /api/v1/auth/login
GET  /api/v1/auth/me
POST /api/v1/auth/logout

GET|POST       /api/v1/businesses
GET|PUT        /api/v1/businesses/{id}
GET|POST       /api/v1/couriers
GET|PUT        /api/v1/couriers/{id}
GET|POST       /api/v1/agencies
GET|PUT        /api/v1/agencies/{id}
GET|POST       /api/v1/earnings
POST           /api/v1/earnings/{id}/approve
GET            /api/v1/notifications
GET            /api/v1/notifications/unread
PATCH          /api/v1/notifications/{id}/read
POST           /api/v1/notifications/read-all
GET            /api/v1/dashboard
```

Sanctum token ile kimlik doğrulama gerekir (login hariç).

## Testler

```bash
php artisan test
```
