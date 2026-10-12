# Deploying NONXSTUDIOS

Everything in this folder is for getting the app onto a server that already
runs a web server and may host other sites. Nothing here invents credentials —
you supply the database password, admin account and SMTP details.

## 0. What you need to give me / have ready

| Item | Why |
|------|-----|
| SSH target (`user@host`, port if not 22) | To connect at all |
| Domain (or subdomain) | `APP_URL`, canonical URLs, sitemap |
| Whether PHP 8.2+ / MySQL are installed | Probe tells us; may need install |
| Where the doc root should live | e.g. `/var/www/nonxstudios` |
| DB name / user / password | Create a **dedicated, least-privilege** user |

## 1. Probe the server (read-only, safe)

```bash
./deploy/probe.sh user@host [-p PORT]
```

Prints OS, PHP version + extensions, PHP-FPM, nginx/Apache, database client,
composer/git/rsync, **existing sites** (so we don't disturb them), sudo access
and disk space. It changes nothing.

## 2. Sync the code

```bash
./deploy/sync.sh user@host:/var/www/nonxstudios [-p PORT] [--dry-run]
```

Excludes `.env`, `storage/`, `.git/` and `vendor/`, so production secrets and
uploaded media are never overwritten by your local copy. Use `--dry-run` first.

## 3. Configure and install on the server

```bash
ssh user@host
cd /var/www/nonxstudios

cp deploy/.env.production.example .env
# Edit .env — set APP_URL, DB_* , and a fresh APP_KEY:
#   php -r "echo bin2hex(random_bytes(32));"

./deploy/deploy.sh \
  --admin-email=you@example.com \
  --admin-password='a-strong-password' \
  --admin-name="Your Name"
```

`deploy.sh` checks PHP ≥ 8.2 and required extensions, fixes `storage/`
permissions, installs Composer deps if present, applies the schema, seeds
settings, lints every PHP file and reports.

Add `--fresh` to (re)create the schema and load the six demo projects. **Do not
run `--fresh` on a database with real content — it drops the tables.**

## 4. Point the web server at `public/`

Use `deploy/apache-vhost.conf` or `deploy/nginx.conf` as a starting point. The
document root **must** be `.../nonxstudios/public` — the app, config, storage
and vendor directories then sit outside the web root and are unreachable over
HTTP.

## 5. HTTPS and hardening

- Enable TLS (certbot / your proxy), then set `SESSION_SECURE=true` in `.env`.
- `APP_ENV=production`, `APP_DEBUG=false`.
- `storage/` must not be inside the doc root (it isn't, with the layout above).
- Keep `.env` readable only by the web user: `chmod 640 .env`.

## 6. Create the admin account later

```bash
php database/seed.php --admin-email=you@example.com --admin-password='…'
```

## 7. Updates

Re-run steps 2 and 3. `sync.sh` is safe to repeat; `deploy.sh` is idempotent and
won't wipe data unless you pass `--fresh`.
