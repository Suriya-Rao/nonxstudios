#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# NONXSTUDIOS — sync the project to the server over rsync/SSH
#
#   ./deploy/sync.sh user@host:/var/www/nonxstudios [-p PORT] [--dry-run]
#
# Excludes secrets and runtime state so nothing local leaks to the server and
# existing production data is never overwritten:
#   .env            (server keeps its own)
#   storage/*       (server keeps its own uploads + logs)
#   .git, vendor, node_modules, .DS_Store
#
# Uses --delete ONLY within the synced tree, but storage/ and .env are
# protected from deletion via --filter rules below.
# ---------------------------------------------------------------------------
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST="${1:-}"
PORT=""
DRY=""
shift || true
while [ $# -gt 0 ]; do
    case "$1" in
        -p) PORT="${2:-}"; shift 2 ;;
        --dry-run) DRY="--dry-run"; shift ;;
        *) echo "Unknown option: $1" >&2; exit 2 ;;
    esac
done

if [ -z "$DEST" ]; then
    echo "usage: $0 user@host:/path [-p PORT] [--dry-run]" >&2
    exit 2
fi

SSH_CMD="ssh -o BatchMode=yes -o ConnectTimeout=10"
if [ -n "$PORT" ]; then SSH_CMD="$SSH_CMD -p $PORT"; fi

# macOS ships openrsync, which does not understand GNU's --info=. Detect it and
# fall back to flags both implementations accept.
if rsync --version 2>/dev/null | grep -qi 'openrsync'; then
    RSYNC_FLAGS="-azh --stats"
else
    RSYNC_FLAGS="-az --human-readable --info=stats1,progress2"
fi

echo "==> Syncing $ROOT  ->  $DEST ${DRY:+(dry run)}"
# shellcheck disable=SC2086
rsync $RSYNC_FLAGS $DRY \
    --delete \
    -e "$SSH_CMD" \
    --exclude '.env' \
    --exclude '.env.local' \
    --exclude '.env.production' \
    --exclude '.env.staging' \
    --exclude '.env.*.local' \
    --include '.env.example' \
    --include '.env.production.example' \
    --include 'deploy/.env.production.example' \
    --exclude '.git/' \
    --exclude '.gitignore' \
    --exclude 'vendor/' \
    --exclude 'node_modules/' \
    --exclude '.DS_Store' \
    --exclude 'storage/logs/*' \
    --exclude 'storage/uploads/*' \
    --filter 'P storage/logs/' \
    --filter 'P storage/uploads/' \
    --filter 'P storage/' \
    "$ROOT/" "$DEST/"

echo "==> Sync complete."
echo "    On the server, run:  cd <path> && ./deploy/deploy.sh"
