==============================================================
 WH — Warehouse Information System  (Laravel 12 + Livewire 3)
 DEPLOYMENT PACKAGE — read this first
==============================================================

This zip is the COMPLETE application, ready to upload. It already
includes:
  - vendor/            (all PHP dependencies — no "composer install" needed)
  - public/build/      (pre-built CSS/JS — no Node/npm needed)
  - .env               (production config — EDIT a few values, see step 3)

The database is delivered separately as wh_db.sql (already sent).
The data (users, records, photos) is inside the .sql, so you do NOT
need to run "php artisan migrate".

--------------------------------------------------------------
 REQUIREMENTS (please confirm)
--------------------------------------------------------------
  - PHP 8.2 or newer  (Laravel 12 will NOT run on PHP 8.1)
      extensions: pdo_mysql, mbstring, openssl, tokenizer, xml,
      ctype, json, bcmath, fileinfo, gd, zip, curl
  - MySQL 8.0+ (or MariaDB 10.4+) with wh_db.sql imported
  - The web server's DOCUMENT ROOT must point to the  public/  folder
    of this project (NOT the project root). This is required for routing
    and security (it keeps .env and source code private).

--------------------------------------------------------------
 STEPS
--------------------------------------------------------------
1) Upload & extract this package to the server
   (e.g. /home/wh.namtheun2.com/app  — anywhere is fine).

2) Point the site's document root to:   <that path>/public

3) Edit the  .env  file — set the lines marked "### EDIT":
     APP_URL          -> the real site address
     DB_DATABASE      -> the database name you imported wh_db.sql into
     DB_USERNAME      -> the DB user
     DB_PASSWORD      -> the DB password
   (APP_KEY is already set. Leave APP_DEBUG=false.)
   When the site is on HTTPS, also set: SESSION_SECURE_COOKIE=true

4) Make these writable by the web server user (www-data):
     chown -R www-data:www-data storage bootstrap/cache
     chmod -R 775 storage bootstrap/cache

5) If you have shell access, run (from the project folder):
     php artisan storage:link        # makes uploaded photos visible
     php artisan config:cache
     php artisan route:cache
     php artisan view:cache
   (If no shell: the app still runs without the cache commands. For
    photos without "storage:link", create a symlink manually:
     ln -s ../storage/app/public public/storage )

6) Test:  https://<your-domain>/up      -> should show a green "OK" page
   Then open the site root -> it redirects to the login page.

--------------------------------------------------------------
 FIRST LOGIN (super admin — already in the imported data)
--------------------------------------------------------------
   Email:    khamsone@namtheun2.com
   Password: ChangeMe123!
   >>> Please change this password right after the first login.

--------------------------------------------------------------
 NOTES
--------------------------------------------------------------
 - Nginx/Apache example config + full details: see DEPLOY.html (included).
 - Self-registration is disabled by design (accounts are created by an
   admin / synced from AD later).
 - For automatic borrow reminders, add a cron entry:
     * * * * * cd <project> && php artisan schedule:run >> /dev/null 2>&1
 - HTTPS is required for the mobile "install app" (PWA) feature.

Questions? Contact Khamsone.
