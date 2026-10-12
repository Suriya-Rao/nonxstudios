#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# NONXSTUDIOS — container entrypoint
#
# Waits for the database, then applies the schema (idempotent) before starting
# Apache. Seeding the demo content and creating an administrator are opt-in via
# environment variables, so nothing is invented on your behalf.
#
#   SEED_DEMO=true            load the six demo projects (only if none exist)
#   ADMIN_EMAIL=you@x.com     create/update an administrator
#   ADMIN_PASSWORD=…          required alongside ADMIN_EMAIL (min 10 chars)
#   ADMIN_NAME="Your Name"
# ---------------------------------------------------------------------------
set -euo pipefail

cd /var/www/nonxstudios

log() { printf '[entrypoint] %s\n' "$*"; }

# --- Wait for the database --------------------------------------------------
if [ -n "${DB_HOST:-}" ]; then
    log "Waiting for database at ${DB_HOST}:${DB_PORT:-3306}…"
    for i in $(seq 1 60); do
        if php -r '
            $h = getenv("DB_HOST"); $p = getenv("DB_PORT") ?: "3306";
            $s = @fsockopen($h, (int) $p, $e, $m, 2);
            exit($s ? 0 : 1);
        ' 2>/dev/null; then
            log "Database is reachable."
            break
        fi
        [ "$i" -eq 60 ] && { log "Database did not become ready in time."; exit 1; }
        sleep 2
    done
fi

# --- Ensure storage is writable --------------------------------------------
mkdir -p storage/logs storage/uploads/thumbnails
chown -R www-data:www-data storage 2>/dev/null || true

# --- Apply schema (idempotent) ---------------------------------------------
# Note: --fresh is deliberately NOT used. It drops every table, which would
# wipe real data on each container restart. seed.php skips demo projects whose
# slug already exists, so re-running is safe.
SEED_ARGS=()
if [ "${SEED_DEMO:-false}" != "true" ]; then
    SEED_ARGS+=(--no-demo)
fi
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
    SEED_ARGS+=("--admin-email=${ADMIN_EMAIL}" "--admin-password=${ADMIN_PASSWORD}")
    [ -n "${ADMIN_NAME:-}" ] && SEED_ARGS+=("--admin-name=${ADMIN_NAME}")
fi

log "Applying database schema…"
if php database/seed.php ${SEED_ARGS[@]+"${SEED_ARGS[@]}"}; then
    log "Schema ready."
else
    log "Schema step reported an error — see output above. Starting Apache anyway."
fi

# --- Keep /admin off the public tunnel --------------------------------------
# Unless explicitly allowed, deny /admin to any request carrying
# CF-Connecting-IP (i.e. one that arrived through a Cloudflare Tunnel). Local
# access is unaffected, so the dashboard stays usable from the host.
if [ "${ADMIN_ALLOW_REMOTE:-false}" = "true" ]; then
    log "ADMIN_ALLOW_REMOTE=true — the admin panel is publicly reachable."
    rm -f /etc/apache2/nonx-admin-guard.conf
else
    cp /var/www/nonxstudios/docker/admin-guard.conf /etc/apache2/nonx-admin-guard.conf
    log "Admin panel restricted to local access (set ADMIN_ALLOW_REMOTE=true to override)."
fi

# --- Hand off to Apache -----------------------------------------------------
log "Starting Apache."
exec "$@"
