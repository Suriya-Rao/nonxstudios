<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** Issue an HTTP redirect and stop. */
    public static function redirect(string $path, int $status = 302): never
    {
        $location = str_starts_with($path, 'http') ? $path : url($path);
        if (!headers_sent()) {
            header('Location: ' . $location, true, $status);
        }
        exit;
    }

    public static function json(mixed $data, int $status = 200): never
    {
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status, string $message = '', ?string $title = null): never
    {
        http_response_code($status);

        $titles = [
            403 => 'Forbidden',
            404 => 'Page not found',
            405 => 'Not allowed',
            429 => 'Too many requests',
            500 => 'Something went wrong',
        ];
        // $title lets a caller supply friendlier copy while keeping a standard
        // HTTP status (e.g. CSRF rejection: status 403, heading "Session expired").
        $title ??= $titles[$status] ?? 'Error';

        $view = base_path('app/Views/pages/error.php');
        if (is_readable($view)) {
            require $view;
        } else {
            echo e($title) . ($message !== '' ? ' — ' . e($message) : '');
        }
        exit;
    }

    /** Send the standard security headers for every response. */
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

        // The analytics provider's origins are appended only when a provider is
        // actually configured and enabled, so the default policy stays exactly
        // as tight as it is without analytics.
        $origins = \App\Services\Analytics::cspOrigins();
        $script  = "'self'" . ($origins['script'] !== [] ? ' ' . implode(' ', $origins['script']) : '');
        $connect = "'self'" . ($origins['connect'] !== [] ? ' ' . implode(' ', $origins['connect']) : '');

        header(
            "Content-Security-Policy: default-src 'self'; img-src 'self' data:;"
            . " media-src 'self' https:; style-src 'self' 'unsafe-inline';"
            . " script-src $script; connect-src $connect; font-src 'self';"
            . ' frame-src https://www.youtube.com https://player.vimeo.com;'
            . " object-src 'none'; base-uri 'self'; form-action 'self'"
        );
        if ((bool) config('app.session.secure')) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
}
