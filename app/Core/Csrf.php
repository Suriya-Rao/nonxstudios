<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Per-session synchroniser token. Every state-changing request (POST/PUT/
 * DELETE) must present a matching token.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        Session::start();
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    /** Ready-to-print hidden input field. */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(self::token()) . '">';
    }

    public static function check(?string $token): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || !is_string($token) || $expected === '') {
            return false;
        }
        return hash_equals($expected, $token);
    }

    public static function validateOrFail(?string $token): void
    {
        if (!self::check($token)) {
            Logger::warning('CSRF validation failed', ['uri' => $_SERVER['REQUEST_URI'] ?? '']);
            // 403 rather than the conventional 419: Apache (mod_php) does not
            // recognise the non-standard 419 code and rewrites it to 500.
            // 403 is standard, universally supported, and semantically correct
            // for a rejected request. The user-facing copy still explains it.
            Response::abort(
                403,
                'Your session expired. Please reload the page and try again.',
                'Session expired'
            );
        }
    }
}
