# CRMLog — VPS Kurulum Rehberi (Sıfırdan)

Bu rehber, sıfırlanmış bir **Ubuntu/Debian VPS** üzerinde CRMLog'u production ortamına kurmak içindir.

**Repo:** https://github.com/emirtasnettr/crmparks11

---

## Kurulum özeti

| Adım | Ne yapılır |
|------|------------|
| 1 | VPS'e bağlan |
| 2 | Sistem paketlerini kur (PHP, Nginx, MySQL, Git) |
| 3 | Composer ve Node.js kur |
| 4 | MySQL veritabanı ve kullanıcı oluştur |
| 5 | Projeyi GitHub'dan çek |
| 6 | Kurulum sihirbazını çalıştır |
| 7 | Dosya izinlerini ayarla |
| 8 | Nginx yapılandır |
| 9 | SSL (HTTPS) kur |
| 10 | Panele giriş yap |

Tahmini süre: **20–40 dakika**

---

## Ön hazırlık

Kuruluma başlamadan önce elinizde olsun:

- VPS IP adresi
- Domain adı (ör. `crm.alanadiniz.com`) — DNS kaydı sunucu IP'sine yönlendirilmiş olmalı
- Güçlü bir **veritabanı şifresi** (MySQL için)
- Güçlü bir **admin panel şifresi** (en az 12 karakter)

> Şifreleri bir yere not edin. Kurulum sihirbazı admin şifresini tekrar göstermez.

---

## Adım 1 — VPS'e bağlan

Kendi bilgisayarınızdan:

```bash
ssh root@SUNUCU_IP_ADRESI
```

Örnek:

```bash
ssh root@185.123.45.67
```

---

## Adım 2 — Sistem paketlerini kur

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y nginx mysql-server git unzip curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-sqlite3 php8.3-mbstring \
  php8.3-xml php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl
```

Kontrol:

```bash
php -v
```

Çıktıda `PHP 8.3.x` görmelisiniz.

---

## Adım 3 — Composer kur

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer -V
```

---

## Adım 4 — Node.js kur

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
node -v
npm -v
```

---

## Adım 5 — MySQL veritabanı oluştur

Aşağıdaki komutlarda `GUCLU_DB_SIFRESI` kısmını kendi şifrenizle değiştirin.

```bash
sudo mysql -e "CREATE DATABASE crmlog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'crmlog'@'localhost' IDENTIFIED BY 'GUCLU_DB_SIFRESI';"
sudo mysql -e "GRANT ALL PRIVILEGES ON crmlog.* TO 'crmlog'@'localhost'; FLUSH PRIVILEGES;"
```

> **Dikkat:** Komutların sonuna fazladan karakter eklemeyin. Her satır `;"` ile bitmeli, sonrasında başka harf olmamalı.

Doğrulama:

```bash
sudo mysql -e "SHOW DATABASES LIKE 'crmlog';"
sudo mysql -e "SHOW GRANTS FOR 'crmlog'@'localhost';"
```

### Sık görülen MySQL hataları

| Hata | Anlamı | Çözüm |
|------|--------|-------|
| `database exists` | Veritabanı zaten var | Normal, devam edin |
| `CREATE USER failed` | Kullanıcı zaten var | Normal, devam edin |
| `syntax error near 'v'` | Komut sonuna fazladan karakter eklenmiş | Komutu tekrar, temiz yapıştırın |
| Şifreyi değiştirmek istiyorum | — | `sudo mysql -e "ALTER USER 'crmlog'@'localhost' IDENTIFIED BY 'YENI_SIFRE'; FLUSH PRIVILEGES;"` |

Kurulum sihirbazında kullanacağınız bilgiler:

| Alan | Değer |
|------|-------|
| Veritabanı adı | `crmlog` |
| Kullanıcı adı | `crmlog` |
| Şifre | Adım 5'te belirlediğiniz şifre |
| Sunucu | `127.0.0.1` |
| Port | `3306` |

---

## Adım 6 — Projeyi GitHub'dan çek

```bash
cd /var/www
sudo git clone https://github.com/emirtasnettr/crmparks11.git crmlog
sudo chown -R $USER:www-data crmlog
cd crmlog
```

---

## Adım 7 — Kurulum sihirbazını çalıştır

```bash
chmod +x install.sh
./install.sh
```

`install.sh` sırasıyla şunları yapar:

1. `composer install` (PHP bağımlılıkları)
2. `npm ci` veya gerekirse `npm install` (frontend bağımlılıkları)
3. `npm run build` (CSS/JS derlemesi)
4. `php artisan crmlog:install` (kurulum sihirbazı)

### Sihirbaz soruları ve örnek cevaplar

| Soru | Örnek cevap |
|------|-------------|
| Uygulama URL | `https://crm.alanadiniz.com` (SSL henüz yoksa geçici olarak `http://SUNUCU_IP`) |
| MySQL sunucu | `127.0.0.1` |
| MySQL port | `3306` |
| Veritabanı adı | `crmlog` |
| Veritabanı kullanıcı adı | `crmlog` |
| Veritabanı şifresi | Adım 5'teki şifre |
| Admin şifresi | En az 12 karakter, güçlü şifre (panel girişi) |
| Admin şifresi tekrar | Aynı şifre |

Sihirbaz başarıyla biterse şunları görürsünüz:

```
CRMLog kurulumu tamamlandı.
Giriş adresi: https://.../login
Süper Admin: admin@crmlog.com
```

### npm hatası alırsanız

`npm ci` uyumsuzluk hatası verirse (Linux/macOS lock farkı):

```bash
cd /var/www/crmlog
npm install
npm run build
php artisan crmlog:install
```

---

## Adım 8 — Dosya izinlerini ayarla

```bash
cd /var/www/crmlog
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Adım 9 — Nginx yapılandır

Site dosyasını oluşturun:

```bash
sudo nano /etc/nginx/sites-available/crmlog
```

Aşağıdaki içeriği yapıştırın. `crm.alanadiniz.com` yerine kendi domain'inizi yazın:

```nginx
server {
    listen 80;
    server_name crm.alanadiniz.com;
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

Kaydet: `Ctrl+O` → Enter → `Ctrl+X`

Aktifleştirin:

```bash
sudo ln -s /etc/nginx/sites-available/crmlog /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl reload nginx
```

Tarayıcıda `http://crm.alanadiniz.com` adresini açın — login sayfası gelmeli.

---

## Adım 10 — SSL (HTTPS) kur

Domain DNS'i sunucuya yönlendirilmiş olmalı.

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.alanadiniz.com
```

Sertifika kurulduktan sonra `.env` içindeki URL'nin HTTPS olduğundan emin olun:

```bash
cd /var/www/crmlog
sudo nano .env
```

Şu satırı kontrol edin:

```env
APP_URL=https://crm.alanadiniz.com
```

Kaydedip cache yenileyin:

```bash
php artisan config:cache
```

---

## Adım 11 — Panele giriş

Tarayıcıda:

```
https://crm.alanadiniz.com/login
```

| Rol | E-posta |
|-----|---------|
| Süper Admin | `admin@crmlog.com` |
| Genel Müdür | `mudur@crmlog.com` |
| Operasyon Uzmanı | `operasyon@crmlog.com` |

**Şifre:** Kurulum sihirbazında belirlediğiniz admin şifresi.

---

## Adım 12 — Queue worker (önerilir)

Arka plan işleri için systemd servisi. Hazır birim: `deploy/crmlog-queue.service`

Tek komutla kurulum + doğrulama:

```bash
cd /var/www/crmlog
sudo bash deploy/install-ops.sh
```

Manuel kurulum:

```bash
sudo cp /var/www/crmlog/deploy/crmlog-queue.service /etc/systemd/system/crmlog-queue.service
sudo systemctl daemon-reload
sudo systemctl enable --now crmlog-queue
sudo systemctl status crmlog-queue
```

`.env` içinde `QUEUE_CONNECTION=database` olmalı (`sync` değil).

---

## Adım 13 — Scheduler cron (önerilir)

Hatırlatma komutları (`crmlog:reminders:*`) Laravel scheduler ile çalışır. Cron olmadan tetiklenmezler.

`install-ops.sh` cron dosyasını da kurar. Manuel:

```bash
sudo cp /var/www/crmlog/deploy/crmlog-scheduler.cron /etc/cron.d/crmlog-scheduler
sudo chmod 644 /etc/cron.d/crmlog-scheduler
```

Veya root crontab:

```bash
* * * * * cd /var/www/crmlog && php artisan schedule:run >> /dev/null 2>&1
```

Doğrulama:

```bash
cd /var/www/crmlog
bash deploy/verify-ops.sh
php artisan schedule:list
```

Beklenen çıktıda şunlar görünmeli:

- `crmlog-queue` **active** + **enabled**
- `/etc/cron.d/crmlog-scheduler` mevcut
- `crmlog:reminders:contracts|documents|collections|payments` schedule listesinde

---

## Güncelleme (deploy)

Kod güncellemesi için:

```bash
cd /var/www/crmlog
./deploy.sh
```

`deploy.sh` şunları yapar: `git pull`, composer, npm build, migration, cache yenileme, `queue:restart`.

İlk kez queue/cron kurulacaksa ardından:

```bash
sudo bash deploy/install-ops.sh
```

---

## Sorun giderme

### 500 Internal Server Error

```bash
tail -50 /var/www/crmlog/storage/logs/laravel.log
```

Genelde izin veya `.env` hatasıdır. Adım 8'i tekrarlayın.

### CSS / JS yüklenmiyor

```bash
cd /var/www/crmlog
npm install
npm run build
ls public/build/manifest.json
```

### Görseller görünmüyor

```bash
cd /var/www/crmlog
php artisan storage:link
```

### Giriş yapamıyorum / oturum düşüyor

- HTTPS kullanıyorsanız `.env` içinde `APP_URL` `https://` ile başlamalı
- `SESSION_SECURE_COOKIE=true` yalnızca HTTPS ile çalışır
- Cache temizleyin: `php artisan config:cache`

### Kurulum sihirbazı tekrar çalışmıyor

Kurulum tamamlandıysa kilit dosyası vardır. Yeniden kurulum tehlikelidir:

```bash
php artisan crmlog:install --force
```

### Veritabanı bağlantı hatası

```bash
sudo mysql -u crmlog -p crmlog
```

Şifre sorulursa Adım 5'teki şifreyi girin. Bağlanamıyorsanız kullanıcı/şifreyi sıfırlayın:

```bash
sudo mysql -e "ALTER USER 'crmlog'@'localhost' IDENTIFIED BY 'YENI_SIFRE'; FLUSH PRIVILEGES;"
```

---

## Hızlı komut özeti (kopyala-yapıştır)

Sıfırdan kurulum için tüm akış:

```bash
# 1) Paketler
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server git unzip curl \
  php8.3-fpm php8.3-cli php8.3-mysql php8.3-mbstring php8.3-xml \
  php8.3-curl php8.3-zip php8.3-gd php8.3-bcmath php8.3-intl

# 2) Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 3) Node.js
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# 4) MySQL (şifreyi değiştir!)
sudo mysql -e "CREATE DATABASE crmlog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'crmlog'@'localhost' IDENTIFIED BY 'GUCLU_DB_SIFRESI';"
sudo mysql -e "GRANT ALL PRIVILEGES ON crmlog.* TO 'crmlog'@'localhost'; FLUSH PRIVILEGES;"

# 5) Proje
cd /var/www
sudo git clone https://github.com/emirtasnettr/crmparks11.git crmlog
sudo chown -R $USER:www-data crmlog
cd crmlog
chmod +x install.sh
./install.sh

# 6) İzinler
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 7) Nginx + SSL (domain'i değiştir)
# /etc/nginx/sites-available/crmlog dosyasını oluştur
sudo ln -s /etc/nginx/sites-available/crmlog /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d crm.alanadiniz.com
```

---

## Destek

Kurulumda takılırsanız hata mesajını **olduğu gibi** kopyalayıp gönderin. En sık görülen konular: MySQL şifresi, npm build ve Nginx domain ayarı.
