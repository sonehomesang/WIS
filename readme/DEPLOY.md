# WH — Deployment Guide

Warehouse Information System (Laravel 12 + Livewire 3 + MySQL) → **https://wh.namtheun2.com**

> ⚠️ wh_db ມີ **ຂໍ້ມູນຈິງ**. ໃຊ້ `migrate` (additive) ເທົ່ານັ້ນ — **ຫ້າມ `migrate:fresh`** ໃນ production. Backup ກ່ອນ migrate ສະເໝີ.

---

## 1. Server requirements

| ສິ່ງ | ລຸ້ນ |
|---|---|
| PHP | **8.2+** (8.3 ແນະນຳ) + ext: pdo_mysql, mbstring, gd, zip, bcmath, fileinfo, openssl, curl |
| MySQL | 8.0+ (`mysqldump` + `mysql` client ໃນ PATH — ສຳລັບ Backup/Restore) |
| Composer | 2.x |
| Node | 18+ (build assets ເທົ່ານັ້ນ — ບໍ່ຕ້ອງ run ໃນ production) |
| Web server | Nginx ຫຼື Apache → root = `public/` |
| TLS | **HTTPS cert ຈຳເປັນ** (PWA install + secure cookies) |

---

## 2. `.env` (production)

```env
APP_NAME="WH — Warehouse"
APP_ENV=production
APP_KEY=                       # php artisan key:generate
APP_DEBUG=false                # ⚠️ ຕ້ອງ false
APP_URL=https://wh.namtheun2.com
APP_TIMEZONE=Asia/Vientiane

# DB (production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=wh_db
DB_USERNAME=wh_user
DB_PASSWORD=********

# Sessions / cookies (HTTPS)
SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=wh.namtheun2.com

# Queue — sync = emails ສົ່ງທັນທີ (ບໍ່ຕ້ອງ worker). ຖ້າຢາก async ໃຊ້ database + queue:work
QUEUE_CONNECTION=sync

# Mail (reset-password / verify) — ໃສ່ SMTP ຈິງ
MAIL_MAILER=smtp
MAIL_HOST=...
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="wh@namtheun2.com"

# Super admin (seeder)
SUPER_ADMIN_PASSWORD=********    # ປ່ຽນຫຼັງ login ครั้งแรก

# Backup binaries (ຖ້າ mysqldump/mysql ບໍ່ຢູ່ PATH)
# BACKUP_MYSQLDUMP_BINARY=/usr/bin/mysqldump
# BACKUP_MYSQL_BINARY=/usr/bin/mysql
```

---

## 3. Deploy steps

```bash
# 0) clone / pull
git clone <repo> wh && cd wh        # ຫຼື: git pull

# 1) PHP deps (no dev)
composer install --no-dev --optimize-autoloader

# 2) env + key
cp .env.example .env                # ແລ້ວແກ້ ຄ່າ ຕาม §2
php artisan key:generate

# 3) frontend build
npm ci
npm run build                       # → public/build

# 4) DB schema (additive — backup ກ່ອນ ຖ້າມີ data)
php artisan migrate --force

# 5) seed RBAC + super_admin (idempotent)
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=SuperAdminSeeder --force

# 6) (DB ໃໝ່ ເທົ່ານັ້ນ) import users + verify
#   ກ໋ອບปี้ exports/users.json → server ກ່ອນ
php artisan users:import-json storage/app/migrate/users.json
php artisan users:verify-precreated

# 7) storage symlink (ຮູບ upload)
php artisan storage:link

# 8) cache (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9) permissions (Linux)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

---

## 4. Nginx (ຕົວຢ່າງ)

```nginx
server {
    listen 443 ssl http2;
    server_name wh.namtheun2.com;
    root /var/www/wh/public;

    ssl_certificate     /etc/letsencrypt/live/wh.namtheun2.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/wh.namtheun2.com/privkey.pem;

    index index.php;
    charset utf-8;
    client_max_body_size 30M;          # CSV / photo upload

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~ /\.(?!well-known).* { deny all; }
}
# port 80 → redirect 443
server { listen 80; server_name wh.namtheun2.com; return 301 https://$host$request_uri; }
```

HTTPS cert: `certbot --nginx -d wh.namtheun2.com`

---

## 5. Cron (scheduler)

ໃສ່ crontab ຂອງ www-data — ສຳລັບ **borrow reminder ອັດຕะໂນมัด (08:00)**:

```cron
* * * * * cd /var/www/wh && php artisan schedule:run >> /dev/null 2>&1
```

(ຖ້າ ໃຊ້ `QUEUE_CONNECTION=database` ແທນ sync → ຕ້ອງ supervisor run `php artisan queue:work`.)

---

## 6. Backup & restore

- **Manual**: Settings › Backup (admin: create/download/delete · super_admin: restore).
- ໄຟລ໌ ຢູ່ `storage/app/backups/*.sql.gz` (ບໍ່ເปิด public).
- **Auto daily** (ແນະນຳ): ເພີ່ມ `Schedule::command('db:backup')` ຫຼື cron `mysqldump` → off-site.
- **Restore** (disaster): Settings › Backup → ເລືອກ → ພິມ `RESTORE` (super_admin, **ໃນ maintenance**).

---

## 7. Security checklist (ก่อน go-live)

- [ ] `APP_DEBUG=false` · `APP_ENV=production`
- [ ] HTTPS + `SESSION_SECURE_COOKIE=true`
- [ ] `APP_KEY` set · `.env` perms 600 · ບໍ່ commit
- [ ] super_admin password ປ່ຽນ ຫຼັງ login
- [ ] self-registration ປິດແລ້ວ (✓ ໃນ code) · locked/pending login block (✓)
- [ ] backup ทดสอบ download + restore (ໃນ staging)
- [ ] DB user ສິດ ສະເພาະ wh_db

---

## 8. Pending — Active Directory (#3)

ปัจจุบัน auth = local (email/password). IT ต้องการ sync ກັບ AD ນ້ຳເທີນ:
- ໃຊ້ `directorytree/ldaprecord-laravel` (LDAP bind) **ຫຼື** Azure AD SSO (SAML/OIDC ຖ້า M365).
- 84 pre-created users = **email-matched → พร้อม** bind.
- **ຕ້ອງ ຂໍ້ມູນ IT**: AD host · base DN · service account · LDAP ຫຼື Azure.

---

## 9. Verify after deploy

1. `https://wh.namtheun2.com/up` → 200
2. Login (super_admin) → Dashboard render
3. ເປີດ ແຕ່ລະ menu (Inventory/Borrow/Deposit/Request/DA/OGA/Expo/Settings)
4. PWA: Chrome → Install app (ต้อง HTTPS)
5. Settings › Backup → ສ້າງ 1 backup
6. `php artisan about` → ກວດ env/cache

## 10. Rollback

```bash
git checkout <prev-tag> && composer install --no-dev && npm run build
php artisan migrate:rollback --step=1    # ຖ້າ migration ໃໝ່ມีปัญหา
# ຫຼື restore DB ຈาก backup (Settings › Backup ຫຼື mysql < dump.sql)
php artisan optimize:clear && php artisan config:cache
```
