<?php

declare(strict_types=1);

namespace App\Core;

use App\Models\User;
use App\Models\LoginAttempt;

/**
 * Administrator authentication: password hashing, session fixation protection,
 * and per-IP + per-account login throttling.
 */
final class Auth
{
    private const SESSION_KEY = '_admin_user_id';

    private static ?array $cachedUser = null;

    public static function attempt(string $email, string $password, string $ip): array
    {
        $email = strtolower(trim($email));

        if (LoginAttempt::isLocked($email, $ip)) {
            return ['ok' => false, 'error' => 'Too many failed attempts. Please wait a few minutes and try again.'];
        }

        $user = User::findByEmail($email);

        // Always run a hash comparison to keep timing roughly constant.
        $hash = $user['password_hash'] ?? '$2y$12$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinva';
        $valid = password_verify($password, $hash);

        if ($user === null || !$valid) {
            LoginAttempt::record($email, $ip, false);
            Logger::warning('Failed admin login', ['email' => $email, 'ip' => $ip]);
            return ['ok' => false, 'error' => 'Those credentials do not match our records.'];
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            User::updatePasswordHash((int) $user['id'], password_hash($password, PASSWORD_DEFAULT));
        }

        LoginAttempt::record($email, $ip, true);
        LoginAttempt::clear($email, $ip);

        Session::regenerate(true);
        Session::put(self::SESSION_KEY, (int) $user['id']);
        Session::put('_last_activity', time());
        Logger::info('Admin login', ['email' => $email, 'ip' => $ip]);

        return ['ok' => true];
    }

    public static function check(): bool
    {
        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            return false;
        }

        // Idle timeout.
        $last = (int) Session::get('_last_activity', 0);
        $max  = (int) config('app.session.lifetime', 7200);
        if ($last > 0 && (time() - $last) > $max) {
            self::logout();
            return false;
        }
        Session::put('_last_activity', time());

        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }
        $id = Session::get(self::SESSION_KEY);
        if (!$id) {
            return null;
        }
        self::$cachedUser = User::find((int) $id);
        return self::$cachedUser;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_KEY);
        Session::forget('_last_activity');
        Session::regenerate(true);
        self::$cachedUser = null;
    }

    /** Guard for admin pages: redirects anonymous users to the login screen. */
    public static function requireAdmin(): void
    {
        if (!self::check() || !self::isAdmin()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect('/admin/login');
        }
    }
}
