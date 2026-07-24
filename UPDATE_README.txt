==============================================================
 WH — UPDATE PACKAGE  (security hardening + account invite)
==============================================================

WHAT THIS IS
A PARTIAL update — only the files that changed since the last upload.
Extract it OVER the existing app folder on the server (the folder where
app/, vendor/, public/ already live). It MERGES/overwrites those files.
It does NOT touch your .env or storage/ — keep those as they are.

INCLUDED
- Changed app code: app/, routes/, bootstrap/, config/, database/seeders/, resources/
- public/build/  (rebuilt CSS/JS — replaces the old build)
- vendor/guzzlehttp/guzzle + vendor/guzzlehttp/psr7  (+ composer.lock) — security patch
- .env.example  (reference only — do NOT overwrite your real .env)

STEPS (on the server)
1) BACKUP first — Settings > Backup in the app (or mysqldump).
2) Upload & extract this zip over the app folder (merge / overwrite).
   ⚠️ Do NOT overwrite  .env  or  storage/.
3) (Optional) add to your .env:   LOCAL_AUTH=true
   (it defaults to true anyway; set it to false later when LDAP/AD is wired.)
4) In the app folder run:
       php artisan optimize:clear
       php artisan config:cache
       php artisan route:cache
       php artisan view:cache
5) Verify: open  https://wh.namtheun2.com/login  — page loads and login works.

WHAT CHANGED
- Security: closed a PDF-download leak (a user could fetch other people's PDFs
  by changing the id), added HTTP security headers, throttled password-reset,
  patched Guzzle/psr7 vulnerabilities.
- Accounts: new users now SET THEIR OWN password via an emailed link (expires
  in 60 min). Admin can copy the link to hand over directly if email isn't
  ready. Minimum password length is 10.

ONE-TIME CLEANUP
- Reset the password of  phonesavanhp@namtheun2.com  (it had a temporary test
  password). Easiest: Settings > Users > the key (🔑) button to issue a
  set-password link — or have that user click "Forgot password".

EMAIL (SMTP) — needed for the links to actually send
- Configure MAIL_* in .env (MAIL_MAILER=smtp + host/port/username/password).
  Until SMTP is set up, use the "copy link" box in Settings > Users and send
  the link to the person yourself.
