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
