<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function path(): string
    {
        $uri  = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = '/' . trim(rawurldecode($path), '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $value = $_POST[$key] ?? $_GET[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::input($key, $default);
        return is_scalar($value) ? trim((string) $value) : $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::input($key);
        return is_numeric($value) ? (int) $value : $default;
    }

    public static function bool(string $key): bool
    {
        $value = self::input($key);
        return in_array($value, ['1', 'on', 'true', 'yes'], true);
    }

    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    public static function only(array $keys): array
    {
        $out = [];
        foreach ($keys as $key) {
            $out[$key] = self::input($key);
        }
        return $out;
    }

    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * The visitor's IP address.
     *
     * REMOTE_ADDR is the connection peer, which behind a reverse proxy or
     * tunnel is the proxy itself — every visitor would appear to share one
     * address, and anything keyed on IP (the contact rate limiter, login
     * throttling) would treat the whole internet as a single client.
     *
     * Proxy-supplied headers are therefore consulted, but only from the list
     * in app.security.trusted_ip_headers. That list is empty by default for a
     * directly-served site, because a client can send these headers itself and
     * otherwise forge an identity to dodge rate limits.
     */
    public static function ip(): string
    {
        foreach ((array) config('app.security.trusted_ip_headers', []) as $header) {
            $value = self::serverFromHeader((string) $header);
            if ($value === null) {
                continue;
            }
            // X-Forwarded-For is a comma-separated chain: client, proxy, proxy.
            // The left-most entry is the original client.
            $candidate = trim(explode(',', $value)[0]);
            if (filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return $candidate;
            }
        }

        return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    /**
     * The host the current request was addressed to, e.g. "example.com" or
     * "localhost:8080". Null when there is no usable request (CLI) or when the
     * Host header is not trusted.
     */
    public static function host(): ?string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (!is_string($host) || $host === '') {
            $host = (string) ($_SERVER['SERVER_NAME'] ?? '');
        }

        $host = strtolower(trim($host));
        if ($host === '') {
            return null;
        }

        // This value ends up inside href/src attributes, so it is restricted to
        // a plain host[:port]. Anything else (spaces, slashes, quotes) is
        // rejected rather than escaped, because no legitimate Host needs them.
        if (preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/', $host) !== 1) {
            return null;
        }

        // Optional allowlist. Empty (the default) means "trust the Host header",
        // which is what almost every framework does; setting it pins the site to
        // known names, which matters when a stale DNS record points at the box.
        $allowed = array_filter(array_map('strtolower', (array) config('app.security.trusted_hosts', [])));
        if ($allowed !== [] && !in_array($host, $allowed, true)) {
            return null;
        }

        return $host;
    }

    /** "http" or "https" for the current request. */
    public static function scheme(): string
    {
        // When TLS terminates upstream (tunnel, load balancer) Apache sees a
        // plain HTTP connection, so the original scheme only survives in a
        // forwarded header. Consulted only when explicitly configured, for the
        // same reason as trusted_ip_headers.
        foreach ((array) config('app.security.trusted_proto_headers', []) as $header) {
            $value = self::serverFromHeader((string) $header);
            if ($value === null) {
                continue;
            }
            $proto = strtolower(trim(explode(',', $value)[0]));
            if ($proto === 'https' || $proto === 'http') {
                return $proto;
            }
        }

        $https = $_SERVER['HTTPS'] ?? '';
        return is_string($https) && $https !== '' && strtolower($https) !== 'off' ? 'https' : 'http';
    }

    /**
     * The origin the page was requested from, e.g. "https://example.com".
     *
     * Links and asset URLs must be built against this, not against the fixed
     * APP_URL setting: a site reached through a tunnel, a reverse proxy or a
     * second hostname would otherwise emit links back to the configured host,
     * so every asset 404s for the visitor and CSP form-action blocks the forms.
     */
    public static function origin(): ?string
    {
        $host = self::host();
        return $host === null ? null : self::scheme() . '://' . $host;
    }

    /** Reads an HTTP_* server var for a header name like "CF-Connecting-IP". */
    private static function serverFromHeader(string $header): ?string
    {
        if ($header === '') {
            return null;
        }
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
        $value = $_SERVER[$key] ?? null;
        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function userAgent(): string
    {
        return substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    }

    public static function isAjax(): bool
    {
        return strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }
}
