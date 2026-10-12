<?php

declare(strict_types=1);

use App\Core\Env;

if (!function_exists('env')) {
    /**
     * Read an environment value with type coercion for booleans/nulls.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);
        if ($value === false) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)'   => true,
            'false', '(false)' => false,
            'null', '(null)'   => null,
            'empty', '(empty)' => '',
            default            => $value,
        };
    }
}

if (!function_exists('e')) {
    /** Escape output for HTML context. The single canonical XSS guard. */
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];
        $parts = explode('.', $key);
        $file  = array_shift($parts);

        if (!isset($cache[$file])) {
            $path = base_path("config/$file.php");
            $cache[$file] = is_readable($path) ? require $path : [];
        }

        $value = $cache[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }

        return $value;
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $base = dirname(__DIR__, 2);
        return $path === '' ? $base : $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('public_path')) {
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path !== '' ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute URL for a path.
     *
     * The origin is taken from the current request, so the site works when it
     * is reached by any hostname — a tunnel, a staging domain, a reverse proxy
     * — without rebuilding. APP_URL is only the fallback for contexts with no
     * request (CLI, the seeder, cron), and is the right value to set for
     * canonical URLs behind a proxy.
     *
     * Using the request origin rather than a fixed base matters beyond
     * cosmetics: a page served over a tunnel that emitted APP_URL-based asset
     * links would make every visitor's browser request those assets from their
     * own machine, so the page would load unstyled and unstyled-only.
     */
    function url(string $path = ''): string
    {
        $origin = \App\Core\Request::origin()
            ?? rtrim((string) env('APP_URL', ''), '/');

        return $origin . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /**
     * Cache-busting asset URL using the file mtime.
     */
    function asset(string $path): string
    {
        $path = ltrim($path, '/');
        $full = public_path($path);
        $ver  = is_file($full) ? '?v=' . filemtime($full) : '';
        return url($path) . $ver;
    }
}

require_once __DIR__ . '/icons.php';
require_once __DIR__ . '/markdown.php';
