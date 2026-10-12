#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# NONXSTUDIOS — READ-ONLY server probe
#
# Prints what the target server already has, WITHOUT changing anything.
# Safe to run against a production box that hosts other sites.
#
#   ./deploy/probe.sh user@host [-p PORT]
# ---------------------------------------------------------------------------
set -uo pipefail

TARGET="${1:-}"
PORT=""
[ "${2:-}" = "-p" ] && PORT="${3:-}"

if [ -z "$TARGET" ]; then
    echo "usage: $0 user@host [-p PORT]" >&2
    exit 2
fi

SSH_OPTS=(-o BatchMode=yes -o ConnectTimeout=8)
[ -n "$PORT" ] && SSH_OPTS+=(-p "$PORT")

REMOTE='
echo "----- OS -----"
( . /etc/os-release 2>/dev/null && echo "$PRETTY_NAME" ) || uname -a
echo
echo "----- Kernel / arch -----"
uname -srm
echo
echo "----- PHP -----"
if command -v php >/dev/null 2>&1; then
    php -v | head -1
    echo "SAPI binaries: $(ls /usr/bin/php* /usr/local/bin/php* 2>/dev/null | tr "\n" " ")"
    echo "Extensions: $(php -m 2>/dev/null | grep -iE "^(pdo|pdo_mysql|fileinfo|json|mbstring|gd|curl|openssl)$" | tr "\n" " ")"
else
    echo "php: NOT INSTALLED"
fi
echo
echo "----- PHP-FPM -----"
( ls /etc/php/*/fpm 2>/dev/null || ls /run/php 2>/dev/null ) | head -5 || echo "php-fpm: not found"
echo
echo "----- Web server -----"
(command -v nginx && nginx -v 2>&1) || echo "nginx: not installed"
(command -v apache2 && apache2 -v 2>&1 | head -1) || (command -v httpd && httpd -v 2>&1 | head -1) || echo "apache: not installed"
echo
echo "----- Database -----"
(command -v mysql && mysql --version) || echo "mysql client: not installed"
(command -v mariadb && mariadb --version) || true
echo
echo "----- Composer / git / rsync -----"
(command -v composer && composer --version) || echo "composer: not installed"
(command -v git && git --version) || echo "git: not installed"
(command -v rsync && rsync --version | head -1) || echo "rsync: not installed"
echo
echo "----- Sites present (be careful not to disturb these) -----"
ls -1 /etc/nginx/sites-enabled 2>/dev/null || ls -1 /etc/nginx/conf.d 2>/dev/null || ls -1 /etc/apache2/sites-enabled 2>/dev/null || echo "(none detected)"
echo
echo "----- Existing web roots -----"
ls -ld /var/www/* 2>/dev/null | head -20 || echo "(no /var/www)"
echo
echo "----- Who am I / sudo -----"
id
sudo -n true 2>/dev/null && echo "passwordless sudo: YES" || echo "passwordless sudo: NO (will need your help for system changes)"
echo
echo "----- Disk -----"
df -h / 2>/dev/null | tail -1
echo
echo "----- Candidate deploy target writability -----"
for d in /var/www /srv /home; do [ -w "$d" ] && echo "$d: writable" || echo "$d: not writable"; done
'

echo "==> Probing $TARGET (read-only)"
# shellcheck disable=SC2029
ssh "${SSH_OPTS[@]}" "$TARGET" "bash -s" <<<"$REMOTE"
