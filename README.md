# NONXSTUDIOS — Creative Studio Website

A production-ready website and portfolio CMS for a creative studio working in
motion graphics, graphic design, video editing, 3D/CGI and Unreal Engine
production.

- **Frontend:** HTML5, CSS3, vanilla JavaScript, GSAP + ScrollTrigger, Three.js
- **Backend:** PHP 8.2+ (tested on 8.5), MySQL / MariaDB, PDO prepared statements
- **No build step.** No Node, React or bundler required.

---

## Contents

1. [Requirements](#1-requirements)
2. [Local setup](#2-local-setup)
3. [Docker (recommended)](#3-docker-recommended)
4. [Create the first administrator](#4-create-the-first-administrator)
5. [Configure SMTP](#5-configure-smtp)
6. [Project structure](#6-project-structure)
7. [How the security model works](#7-how-the-security-model-works)
8. [Deployment to PHP hosting](#8-deployment-to-php-hosting)
9. [Production checklist](#9-production-checklist)
10. [Implemented features](#10-implemented-features)
11. [Tests performed](#11-tests-performed)
12. [Known limitations](#12-known-limitations)

---

## 1. Requirements

| Requirement | Version | Notes |
|---|---|---|
| PHP | 8.2 or newer | `pdo_mysql`, `fileinfo`, `mbstring`, `openssl`, `json` |
| MySQL / MariaDB | 8.0 / 10.4+ | utf8mb4 |
| Composer | 2.x | Optional — a fallback autoloader ships with the project |
| GD | any | Only needed for `database/seed.php` placeholder artwork |

Check your PHP setup:

```bash
php -v
php -m | grep -E 'pdo_mysql|fileinfo|mbstring|openssl'
```

---

## 2. Local setup

### 2.1 Get the code and install dependencies

```bash
cd /path/to/nonxstudios
composer install          # optional; generates vendor/autoload.php
```

> If Composer is unavailable, the application still runs: `bootstrap.php`
> registers a PSR-4 autoloader when `vendor/autoload.php` is missing.

### 2.2 Create the environment file

```bash
cp .env.example .env
```

Generate an application key and paste it into `.env` as `APP_KEY`:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Set at minimum `APP_URL`, `APP_ENV`, `APP_DEBUG`, and the `DB_*` values.

### 2.3 Create the database

```bash
mysql -u root -p -e "CREATE DATABASE nonxstudios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER 'nonx_user'@'localhost' IDENTIFIED BY 'a-strong-password';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON nonxstudios.* TO 'nonx_user'@'localhost'; FLUSH PRIVILEGES;"
```

Then point `.env` at it:

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=nonxstudios
DB_USER=nonx_user
DB_PASS=a-strong-password
```

### 2.4 Install the schema and demo content

```bash
# Schema + site settings + 6 demo projects (with generated placeholder artwork)
php database/seed.php

# Or, to drop and recreate everything first
php database/seed.php --fresh

# Schema and settings only, no demo projects
php database/seed.php --no-demo
```

The seeder is idempotent: projects are matched by slug and skipped if present.

### 2.5 Run the site

```bash
php -S localhost:8000 -t public
```

| URL | Purpose |
|---|---|
| http://localhost:8000 | Public website |
| http://localhost:8000/work | Portfolio |
| http://localhost:8000/admin | Studio dashboard |

> The PHP built-in server is for development only. It serves everything through
> `public/index.php`, so both the site and `/admin` work from the single
> document root.

---

## 3. Docker (recommended)

The repository ships a self-contained stack — Apache + PHP 8.2 (mod_php) and
MariaDB 10.11 — so it runs identically on any machine with Docker. Nothing but
Docker is required on the host.

### 3.1 Configure

```bash
cp .env.example .env
```

Set at minimum these values in `.env`:

| Key | Notes |
|---|---|
| `APP_KEY` | `php -r "echo bin2hex(random_bytes(32));"` |
| `DB_PASS` | password for the application database user |
| `DB_ROOT_PASS` | password for MariaDB `root` (used only to initialise the volume) |
| `APP_URL` | the address you will browse, e.g. `http://localhost:8080` |

Optionally, to seed content and create your admin on first boot:

```bash
SEED_DEMO=true
ADMIN_EMAIL=you@yourstudio.com
ADMIN_PASSWORD='Choose-A-Strong-Password'
ADMIN_NAME="Your Name"
```

### 3.2 Start

```bash
docker compose up -d --build
```

The entrypoint waits for MariaDB, applies the schema (idempotently), and starts
Apache. Watch it with:

```bash
docker compose logs -f web
```

Then browse **http://localhost:8080** (`WEB_PORT` changes the port).

### 3.3 Common commands

```bash
docker compose down            # stop, keep the database
docker compose down -v         # stop and DELETE all data
docker compose up -d --build   # rebuild after code changes
docker compose exec web php database/seed.php --admin-email=you@x.com --admin-password='…'
```

### 3.4 Notes

- **`--fresh` is never run automatically.** It drops every table. Demo seeding
  only inserts projects whose slug does not already exist, so restarts are safe.
- Uploads and logs live in named volumes (`uploads`, `logs`) and survive
  rebuilds; the database lives in `db_data`.
- The database is **not** published to the host — only the `web` service can
  reach it, over the internal Docker network.
- The image copies the code in and sets `opcache.validate_timestamps=0`. After
  changing PHP files, rebuild (or restart) the container.
- `.env` is excluded by `.dockerignore`, so secrets are never baked into an
  image layer.

---

## 4. Create the first administrator

There is no public sign-up. Create the first admin from the command line:

```bash
php database/seed.php \
  --admin-email="you@yourstudio.com" \
  --admin-password='Choose-A-Strong-Password' \
  --admin-name="Your Name"
```

Rules enforced by the seeder:

- Password must be at least 10 characters.
- Email must be a valid address.
- Re-running the same command with an existing email **updates** that
  administrator's password (useful for recovery).

Passwords are stored with `password_hash()` (bcrypt) and re-hashed
automatically on login if PHP's default algorithm changes.

---

## 5. Configure SMTP

Email notifications are **optional**. With `MAIL_ENABLED=false` (the default),
inquiries are still saved to the database and shown in the dashboard — you
simply do not receive an email copy.

To enable notifications, set these in `.env`:

```
MAIL_ENABLED=true
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls          # tls (587) or ssl (465)
MAIL_FROM_ADDRESS=studio@yourdomain.com
MAIL_FROM_NAME="NONXSTUDIOS"
MAIL_TO_ADDRESS=studio@yourdomain.com
```

Notes:

- A dependency-free SMTP client (sockets, STARTTLS/SSL, AUTH LOGIN) is included
  in `app/Services/Mailer.php`. If you prefer PHPMailer, `composer require
  phpmailer/phpmailer` and swap the call inside `Mailer::send()`.
- Credentials are read from the environment and are **never** exposed to the
  browser.
- If sending fails, the failure is logged to `storage/logs/` and the visitor
  still sees a success message — their inquiry has been stored regardless.

---

## 6. Project structure

```
public/                 ← web root (point your vhost here)
    index.php           front controller
    .htaccess           rewrite + caching + headers
    assets/css|js|images|vendor
admin/index.php         admin entry point (also reachable via /admin)
app/
    Core/               Router, Database, Session, Auth, Csrf, Validator, View…
    Controllers/        public controllers
    Controllers/Admin/  dashboard controllers
    Models/             Project, Inquiry, Setting, User, LoginAttempt
    Services/           Uploader (secure uploads), Mailer (SMTP)
    Helpers/            functions.php, icons.php, markdown.php
    Views/              layouts/, components/, pages/, admin/
config/                 app.php, database.php
routes/                 web.php, admin.php
database/               schema.sql, seed.php, placeholder.php
storage/                logs/, uploads/   ← outside the web root
.env.example
composer.json
```

**Uploads live in `storage/uploads/`**, outside the web root. They are streamed
back through `/media/{path}` (`MediaController`), which validates the extension,
resolves the real path, confirms it sits inside the upload directory, and sends
an explicit whitelisted `Content-Type`. Nothing in that directory is ever
executed as PHP.

---

## 7. How the security model works

| Concern | Mitigation |
|---|---|
| SQL injection | 100% PDO prepared statements; `ATTR_EMULATE_PREPARES=false`; no string interpolation of user data |
| Stored XSS | Every dynamic value passes through `e()` (`htmlspecialchars`, `ENT_QUOTES`). Case-study Markdown is escaped **before** formatting is applied, so raw HTML can never be emitted |
| CSRF | Per-session synchroniser token (`Csrf`), verified on every POST; failure → HTTP 419 |
| Password storage | `password_hash()` / `password_verify()`, automatic re-hash on login |
| Session fixation | `session_regenerate_id(true)` on login and logout |
| Session cookies | `HttpOnly`, `SameSite=Lax`, `Secure` when `SESSION_SECURE=true`, `use_strict_mode=1` |
| Brute force | Failed logins recorded per email **and** per IP; lockout after `LOGIN_MAX_ATTEMPTS` within the window |
| Spam | Per-IP rate limit on the contact form, plus a hidden honeypot field |
| Malicious uploads | Real MIME detection via `finfo` (never the client's claim), `getimagesize()` verification, size ceiling, randomised filenames, whitelisted extensions |
| Path traversal | `/media/...` rejects `..`, absolute paths and null bytes; `realpath()` confined to the upload root |
| Unauthorised access | `AdminController::__construct()` calls `Auth::requireAdmin()` — no admin action is reachable without a valid session; `Auth::isAdmin()` checks the role |
| Secret leakage | Credentials come from `.env` (git-ignored); DB errors are logged, never rendered |
| Clickjacking / MIME sniffing | `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` and a CSP are sent on every response |
| PHP execution in uploads | Uploads sit outside the web root; root `.htaccess` additionally blocks `.php` under `storage/uploads/` |

---

## 8. Deployment to PHP hosting

### Recommended: document root at `public/`

Point the vhost/subdomain document root at the `public/` directory. Everything
else stays outside the web root.

Apache:

```apache
<VirtualHost *:443>
    ServerName yourdomain.com
    DocumentRoot /var/www/nonxstudios/public

    <Directory /var/www/nonxstudios/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Ensure `mod_rewrite`, `mod_headers` and `mod_expires` are enabled.

### Shared hosting where the document root is fixed

If you cannot change the document root, place the project **above** `public_html`
and copy/symlink `public/` into it, adjusting the two `require` paths in
`public/index.php`. Alternatively, upload the whole project to `public_html` and
rely on the root `.htaccess`, which denies direct access to `app/`, `config/`,
`database/`, `routes/`, `storage/` and `vendor/`.

### Permissions

```bash
chmod -R 755 /var/www/nonxstudios
chmod -R 775 /var/www/nonxstudios/storage
chown -R www-data:www-data /var/www/nonxstudios/storage
```

Only `storage/` needs to be writable by the web server.

### Environment

Set `APP_ENV=production`, `APP_DEBUG=false`, `SESSION_SECURE=true`, and a fresh
`APP_KEY`. Run `php database/seed.php` once against the production database
(add `--admin-email`/`--admin-password` to create the admin), then delete or
lock down `database/seed.php`.

---

## 9. Production checklist

- [ ] **HTTPS** — install a TLS certificate; set `SESSION_SECURE=true` (this also enables HSTS).
- [ ] **`APP_DEBUG=false`** — never show stack traces to visitors.
- [ ] **Fresh `APP_KEY`** and strong `DB_PASS`; `.env` is not web-accessible.
- [ ] **`.env` is not committed** — confirm it is absent from your repository.
- [ ] **Document root** points at `public/`, or the root `.htaccess` is active.
- [ ] **File permissions** — `storage/` writable, everything else read-only to the web server.
- [ ] **Backups** — schedule database dumps and a copy of `storage/uploads/`.
- [ ] **Remove the seeder** from production, or restrict access to it.
- [ ] **Disable directory listing** (`Options -Indexes`, already set in `.htaccess`).
- [ ] **SMTP credentials** stored in `.env`, not in code.
- [ ] **Monitor `storage/logs/`** for repeated failed logins.
- [ ] **Rotate the admin password** if it was ever shared over an insecure channel.

---

## 10. Implemented features

### Public site

- Cinematic dark homepage: Three.js hero (metallic icosahedron, wireframe shell,
  accent rings, particle field, pointer parallax, paused when offscreen), with a
  pure-CSS fallback and automatic disabling under `prefers-reduced-motion`
- Asymmetric editorial portfolio grid with client-side category filtering
- Six services, four-stage process, about, software toolkit, CTA band
- Project case studies: cover, Markdown case study, gallery, video embed
  (YouTube/Vimeo, privacy-friendly `youtube-nocookie`), related projects
- Fully working contact form with server-side validation
- Privacy Policy and Terms pages
- Custom 404/403/419/429/500 error page
- Dynamic footer with configurable email, socials and a PHP-generated year

### Admin dashboard (`/admin`)

- Login/logout with throttling and session hardening
- Dashboard with project and inquiry statistics
- Projects: create, edit, delete (with confirmation dialog), publish/unpublish,
  feature, reorder, thumbnail upload, multi-image gallery upload, image removal,
  video URL, slug auto-generation with uniqueness enforcement
- Inquiries: list, filter by status/service, search, pagination, detail view,
  status workflow (New → In Progress → Completed → Archived), internal notes,
  delete with confirmation
- Site settings: company name, contact email, hero headline/subtitle, about and
  footer copy, CTA text, services list, social URLs — all validated

### Technical

- SEO: unique titles/descriptions, canonical URLs, Open Graph, Twitter cards,
  XML sitemap, robots.txt, semantic HTML
- Accessibility: skip link, labelled form controls, ARIA states on the mobile
  menu and filters, visible focus rings, `prefers-reduced-motion` support
- Performance: lazy-loaded images, mtime cache-busting, long-lived cache headers,
  no external icon dependency (inline SVG), responsive `clamp()` typography
- Progressive enhancement: all essential content and form navigation work
  without JavaScript

---

## 11. Tests performed

Verified against PHP 8.5.11 and MariaDB 13.0.2, served by `php -S`:

**Syntax** — `php -l` on all 63 PHP files: 0 failures.

**Public routes** — `/`, `/work`, `/work?category=…`, `/services`, `/about`,
`/contact`, `/project/{slug}` (×2), `/privacy`, `/terms`, `/sitemap.xml`,
`/robots.txt` all return 200 with no PHP warnings/notices/deprecations;
unknown paths return a styled 404.

**Database & seeding** — `php database/seed.php --fresh` created the schema,
13 settings, 6 projects, 18 gallery images and 24 generated placeholder JPEGs;
re-running is idempotent.

**Contact form** — valid submission → 302 → success message → row persisted with
the comma-containing budget intact; invalid submission → field-level errors and
old input preserved (PRG); bad CSRF token → 419; per-IP rate limit triggered on
the 4th submission within the window; honeypot field present.

**Authentication** — anonymous access to `/admin` redirects to the login screen;
wrong password rejected; correct password issues a session; 5 consecutive
failures lock the account out, and the correct password is *also* rejected while
locked; lockout clears after success.

**Admin CRUD** — project create with thumbnail + gallery upload (files written,
DB rows created, `/media/...` serves 200 `image/jpeg`); edit updates fields;
delete removes the row; inquiry status and notes updates persist; settings
updates propagate to the public site; a non-`https://` social URL is rejected.

**Security** — a PHP payload renamed `.jpg` was rejected by MIME detection with
no file written to disk; `<script>` and `<img onerror=…>` in a project title
were stored but rendered as escaped, inert text on the public page; `/media/../../.env`
returns 404.

**Assets** — CSS, JS, GSAP, ScrollTrigger, Three.js, hero3d.js and the favicon
all serve 200; Three.js/hero3d load on the homepage only, not on interior pages.

**Accessibility/SEO** — all 7 homepage images carry alt text; all 9 contact-form
controls have associated labels; skip link, canonical, Open Graph and Twitter
tags present; sitemap contains 13 URLs.

---

## 12. Known limitations

- **Demo content is illustrative.** The six seeded projects are sample
  placeholders with procedurally generated abstract artwork. They are labelled
  as demo content and do not represent commissioned client work. Replace them
  through the dashboard.
- **No email verification or password reset flow.** The first administrator is
  created via CLI by design; recovery is done by re-running the seeder with
  `--admin-email` and a new `--admin-password`.
- **Project media is images plus embedded video URLs.** There is no direct video
  file upload — video is expected to be hosted on YouTube/Vimeo and referenced
  by URL. This keeps hosting costs and bandwidth predictable.
- **The bundled SMTP client is minimal** (plain-text AUTH LOGIN over TLS). It
  covers the common providers; if yours requires OAuth2 or a different auth
  mechanism, install PHPMailer and swap the implementation in
  `app/Services/Mailer.php`.
- **Rate limiting and throttling are database-backed.** They work correctly on a
  single server; on a multi-server deployment use a shared store (Redis, etc.).
- **The `in:` validation rule uses `;` as its separator** so option text may
  itself contain commas. Keep this in mind if you add new option sets.
- **Three.js is a large dependency (~650 KB uncompressed).** It is loaded only on
  the homepage and is gated behind motion preferences and pointer type. Compress
  it with gzip/brotli at the server level (already configured in `public/.htaccess`).
- **The seeder generates placeholder art with GD.** If GD is unavailable, run
  `php database/seed.php --no-demo` and add projects through the dashboard.

---

© NONXSTUDIOS. Software names referenced on the site are the property of their
respective owners and are used to describe the studio's working toolkit only.
