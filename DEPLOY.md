# CRMLog — VPS Kurulum Rehberi

Bu rehber, projeyi Ubuntu/Debian tabanlı bir VPS üzerinde production ortamına almak içindir.

## Gereksinimler

| Bileşen | Minimum sürüm |
|---------|----------------|
| PHP | 8.3+ |
| Composer | 2.x |
| Node.js | 20+ |
| npm | 10+ |
| MySQL | 8.0+ (önerilir) |
| Nginx | 1.18+ |

Gerekli PHP eklentileri:

```bash
sudo apt update
sudo apt install -y nginx mysql-server git unzip curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
```

## 1. Projeyi çekin

```bash
cd /var/www
sudo git clone https://github.com/emirtasnettr/crmparks11.git crmlog
sudo chown -R $USER:www-data crmlog
cd crmlog
```

## 2. Bağımlılıkları kurun

```bash
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

## 3. `.env` dosyasını yapılandırın

Production için örnek ayarlar:

```env
APP_NAME=CRMLog
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alanadiniz.com

LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=crmlog
DB_USERNAME=crmlog
DB_PASSWORD=guclu-veritabani-sifresi

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database

# İlk kurulumda admin kullanıcıları için (en az 12 karakter)
ADMIN_INITIAL_PASSWORD=guclu-ve-benzersiz-bir-sifre
```

MySQL veritabanı oluşturma:

```bash
sudo mysql -e "CREATE DATABASE crmlog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'crmlog'@'localhost' IDENTIFIED BY 'guclu-veritabani-sifresi';"
sudo mysql -e "GRANT ALL PRIVILEGES ON crmlog.* TO 'crmlog'@'localhost'; FLUSH PRIVILEGES;"
```

## 4. Veritabanı ve frontend

```bash
php artisan migrate --force
php artisan db:seed --force
npm ci
npm run build
php artisan storage:link
```

## 5. İzinler

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## 6. Cache optimizasyonu

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 7. Nginx yapılandırması

`/etc/nginx/sites-available/crmlog`:

```nginx
server {
    listen 80;
    server_name alanadiniz.com;
    root /var/www/crmlog/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    client_max_body_size 20M;
}
```

Aktifleştirin:

```bash
sudo ln -s /etc/nginx/sites-available/crmlog /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

## 8. SSL (Let's Encrypt)

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d alanadiniz.com
```

## 9. Queue worker (önerilir)

`/etc/systemd/system/crmlog-queue.service`:

```ini
[Unit]
Description=CRMLog Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/crmlog/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/crmlog

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable --now crmlog-queue
```

## 10. Güncelleme (deploy)

Sunucuda `deploy.sh` scriptini kullanabilirsiniz:

```bash
./deploy.sh
```

veya manuel:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl reload php8.3-fpm
```

## İlk giriş

Seed sonrası (yalnızca `ADMIN_INITIAL_PASSWORD` tanımlıysa):

| Rol | E-posta |
|-----|---------|
| Süper Admin | admin@crmlog.com |
| Genel Müdür | mudur@crmlog.com |
| Operasyon | operasyon@crmlog.com |

Şifre: `.env` içindeki `ADMIN_INITIAL_PASSWORD` değeri.

## Sorun giderme

- **500 hatası:** `storage/logs/laravel.log` dosyasını kontrol edin.
- **CSS/JS yüklenmiyor:** `npm run build` ve `public/build` klasörünü kontrol edin.
- **Görseller görünmüyor:** `php artisan storage:link` çalıştırın.
- **Oturum sorunu:** `SESSION_SECURE_COOKIE=true` yalnızca HTTPS ile çalışır.
