<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Services\Analytics;

/**
 * Records the visitor's analytics consent choice.
 *
 * The banner works without JavaScript (the buttons are a plain form POST), and
 * the choice is kept in a first-party cookie so the server can decide whether
 * to emit the vendor script on the very next render. A cookie is deliberate:
 * the decision has to be readable server-side, which rules out localStorage.
 */
final class ConsentController extends BaseController
{
    public const COOKIE = 'nonx_consent';

    /** One year, the common ceiling for remembering a consent choice. */
    private const MAX_AGE = 31_536_000;

    public function store(): void
    {
        Csrf::validateOrFail(Request::input('_token'));

        $choice = Request::string('consent');

        // Only the two values the banner actually submits are accepted, so a
        // crafted request cannot write an arbitrary cookie value.
        if (!in_array($choice, ['accept', 'decline'], true)) {
            Response::abort(400, 'Unrecognised consent choice.', 'Bad request');
        }

        setcookie(self::COOKIE, $choice, [
            'expires'  => time() + self::MAX_AGE,
            'path'     => '/',
            'secure'   => (bool) config('app.session.secure'),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        // Where the request came from, so the visitor lands back where they
        // were rather than being bounced to the home page.
        $back = Request::string('return_to', '/');
        if ($back === '' || !str_starts_with($back, '/') || str_starts_with($back, '//')) {
            $back = '/';
        }

        Response::redirect($back);
    }

    /**
     * Whether the visitor has accepted analytics.
     *
     * Anything other than an explicit "accept" counts as no: a missing cookie,
     * a tampered value or a stale one all fall through to false, which is the
     * safe default for a consent gate.
     */
    public static function granted(): bool
    {
        if (!Analytics::enabled() || !Analytics::requiresConsent()) {
            return false;
        }
        return ($_COOKIE[self::COOKIE] ?? '') === 'accept';
    }
}
