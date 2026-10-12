#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# NONXSTUDIOS — production deployment / update script
#
# Run this ON THE SERVER from the application root, after the code has been
# synced (e.g. via rsync). It is idempotent: re-run it for every update.
#
#   ./deploy/deploy.sh            # install or update
#   ./deploy/deploy.sh --fresh    # (re)create schema + demo data + admin
#
# It never invents credentials. Database and admin details come from `.env`
# and from the flags you pass, so nothing secret is committed to the repo.
# ---------------------------------------------------------------------------
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"

say()  { printf '\033[1;32m==>\033[0m %s\n' "$*"; }
warn() { printf '\033[1;33m!!\033[0m %s\n' "$*" >&2; }
die()  { printf '\033[1;31mxx\033[0m %s\n' "$*" >&2; exit 1; }

FRESH=0
ADMIN_EMAIL=""
ADMIN_PASSWORD=""
ADMIN_NAME=""
while [ $# -gt 0 ]; do
    case "$1" in
        --fresh)          FRESH=1; shift ;;
        --admin-email)    ADMIN_EMAIL="${2:-}"; shift 2 ;;
        --admin-password) ADMIN_PASSWORD="${2:-}"; shift 2 ;;
        --admin-name)     ADMIN_NAME="${2:-}"; shift 2 ;;
        -h|--help)        sed -n '2,14p' "$0"; exit 0 ;;
        *)                die "Unknown option: $1" ;;
    esac
done

# --- 1. PHP ----------------------------------------------------------------
command -v php >/dev/null 2>&1 || die "PHP is not installed or not on PATH."
PHP_VER="$(php -r 'echo PHP_VERSION;')"
say "PHP $PHP_VER detected."
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' \
    || die "PHP 8.2 or newer is required (found $PHP_VER)."
# Capture once: piping `php -m` into `grep -q` would trip `pipefail`, because
# grep exits on the first match and php then dies of SIGPIPE (exit 141).
PHP_MODULES="$(php -m 2>/dev/null || true)"
for ext in pdo pdo_mysql fileinfo json mbstring; do
    printf '%s\n' "$PHP_MODULES" | grep -qi "^${ext}$" \
        || warn "PHP extension '${ext}' does not appear to be enabled."
done

# --- 2. Environment file ---------------------------------------------------
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        warn ".env not found — copying .env.example. EDIT IT before going live."
        cp .env.example .env
    else
        die ".env is missing and there is no .env.example to copy."
    fi
fi
# shellcheck disable=SC1091
grep -qE '^APP_KEY=.{32,}' .env || warn "APP_KEY looks unset or too short. Generate one with: php -r \"echo bin2hex(random_bytes(32));\""

# --- 3. Permissions --------------------------------------------------------
say "Setting storage permissions…"
mkdir -p storage/logs storage/uploads/thumbnails
chmod -R 775 storage 2>/dev/null || warn "Could not chmod storage — check ownership."
find storage -type d -exec chmod 775 {} \; 2>/dev/null || true
find storage -type f -exec chmod 664 {} \; 2>/dev/null || true
if [ -d public/uploads ]; then
    chmod 775 public/uploads 2>/dev/null || true
fi

# --- 4. Composer (optional) ------------------------------------------------
if [ -f vendor/autoload.php ]; then
    say "Composer dependencies present (vendor/autoload.php)."
elif command -v composer >/dev/null 2>&1; then
    say "Installing Composer dependencies (production)…"
    composer install --no-dev --optimize-autoloader --no-interaction
else
    say "No vendor/ and no composer — the built-in fallback autoloader will be used."
fi

# --- 5. Database -----------------------------------------------------------
say "Applying schema and seeding…"
SEED_ARGS=()
if [ "$FRESH" -eq 1 ]; then SEED_ARGS+=(--fresh); fi
if [ -n "$ADMIN_EMAIL" ];    then SEED_ARGS+=("--admin-email=$ADMIN_EMAIL"); fi
if [ -n "$ADMIN_PASSWORD" ]; then SEED_ARGS+=("--admin-password=$ADMIN_PASSWORD"); fi
if [ -n "$ADMIN_NAME" ];     then SEED_ARGS+=("--admin-name=$ADMIN_NAME"); fi

php database/seed.php ${SEED_ARGS[@]+"${SEED_ARGS[@]}"}

# --- 6. Lint ---------------------------------------------------------------
say "Linting PHP files…"
LINT_FAIL=0
while IFS= read -r -d '' f; do
    php -l "$f" >/dev/null 2>&1 || { warn "Syntax error: $f"; LINT_FAIL=1; }
done < <(find app config routes database public -name '*.php' -print0 2>/dev/null)
if [ "$LINT_FAIL" -eq 0 ]; then say "All PHP files passed syntax check."; fi

say "Deployment complete."
echo
echo "Next steps:"
echo "  1. Point your web server document root at:  $ROOT/public"
echo "  2. Ensure HTTPS is enabled, then set SESSION_SECURE=true in .env"
echo "  3. Create your admin account if you have not already:"
echo "       php database/seed.php --admin-email=you@example.com --admin-password='…'"
echo "  4. See deploy/apache-vhost.conf or deploy/nginx.conf for server config."
