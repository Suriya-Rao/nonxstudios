# Server-specific notes — Kasm sandbox (172.30.22.250)

The production deployment target is a **Kasm Workspaces sandbox**
(`ubuntu.6bde526f0dc56f4765dca0d4927e4f40.selfmade.ninja`), reachable only over
the platform's WireGuard VPN at `Suriyarao@172.30.22.250`.

## Important characteristics

- **Ephemeral container.** `/.dockerenv` is present and `/` is a Docker
  `overlay`. Only `/home` is a persistent volume (`/dev/vdb`, xfs).
  → Code in `/home` survives a container restart; **apt-installed packages do not.**
- **External routing.** A Traefik proxy fronts the host. Until the platform
  routes the hostname to this container, external requests get Traefik's
  "404 · Nothing connected here". Apache itself answers fine on `127.0.0.1:80`.
- Passwordless `sudo` is available to the `Suriyarao` user.

## What was installed (repeat after a container reset)

```bash
export DEBIAN_FRONTEND=noninteractive
sudo add-apt-repository -y ppa:ondrej/php
sudo apt-get update

# libgd3 from the PPA must come first: php8.2-gd requires libgd3 >= 2.3.3,
# but jammy ships 2.3.0.
sudo apt-get install -y libgd3=2.3.3-13+ubuntu22.04.1+deb.sury.org+1
sudo apt-get install -y \
  php8.2 php8.2-cli php8.2-common php8.2-mysql php8.2-mbstring \
  php8.2-gd php8.2-xml php8.2-curl php8.2-opcache php8.2-intl \
  libapache2-mod-php8.2 mariadb-server mariadb-client rsync

sudo systemctl enable --now mariadb apache2
sudo a2enmod rewrite headers expires deflate
sudo a2dismod php8.1 && sudo a2enmod php8.2
sudo systemctl restart apache2
```

## Database

```bash
sudo mariadb <<'SQL'
CREATE DATABASE IF NOT EXISTS nonxstudios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'nonx_user'@'localhost' IDENTIFIED BY '<from ~/.nonx_db_pass>';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES
  ON nonxstudios.* TO 'nonx_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

## Layout and permissions

- App: `/home/Suriyarao/apps/nonxstudios`, doc root `public/`
- `chmod 755 /home/Suriyarao/apps` — Apache must traverse this path.
  (`~` is `700` by default, which yields 403.)
- `storage/` owned `Suriyarao:www-data`, mode `775`, setgid on the subdirs.
- `.env` owned `Suriyarao:www-data`, mode `640` — **without group read,
  www-data cannot read it and the app falls back to DB user `root`** and fails
  with `SQLSTATE[HY000] [1698] Access denied for user 'root'@'localhost'`.
- Secrets are stored on the server only: `~/.nonx_db_pass`,
  `~/.nonx_admin_pass` (both `600`).

## Apache vhost

`/etc/apache2/sites-available/nonxstudios.conf` — see `deploy/apache-vhost.conf`.
`000-default` is disabled.

## Status codes: use only standard ones

**Apache (mod_php) rewrites HTTP status codes it does not recognise to 500.**
The app originally used `419` for CSRF failures; on Apache that surfaced as
`500`. It now returns **403** with the title "Session expired". The same applies
to any other non-standard code — stick to 400/401/403/404/429/500/503.

## VPN outage note

Access dropped on 2026-10-12 around 07:35 with `Network is unreachable`. The
`utun4` route to `172.30/16` is still present but no packets flow, so the
WireGuard tunnel itself is down. WireGuard.app manages it; there is no local
`wg`/`wg-quick` CLI. Reconnect via the WireGuard.app menu, or recreate the Kasm
session. The public IP `106.51.76.75` answers on port 22 but rejects this key,
so the VPN is the only route in.
