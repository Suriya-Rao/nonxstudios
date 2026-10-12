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
    /** Build an absolute URL against APP_URL. */
    function url(string $path = ''): string
    {
        $base = rtrim((string) env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
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
