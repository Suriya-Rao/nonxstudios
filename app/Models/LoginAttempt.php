<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Backing store for login throttling. Counts recent failures per email+IP
 * within the configured lockout window.
 */
final class LoginAttempt
{
    public static function record(string $email, string $ip, bool $successful): void
    {
        Database::execute(
            'INSERT INTO login_attempts (email, ip, successful, created_at) VALUES (:email, :ip, :ok, NOW())',
            ['email' => strtolower($email), 'ip' => $ip, 'ok' => $successful ? 1 : 0]
        );
    }

    public static function isLocked(string $email, string $ip): bool
    {
        $window = (int) config('app.security.login_lockout_window', 900);
        $max    = (int) config('app.security.login_max_attempts', 5);

        $row = Database::selectOne(
            'SELECT COUNT(*) AS c FROM login_attempts
             WHERE successful = 0
               AND (email = :email OR ip = :ip)
               AND created_at > (NOW() - INTERVAL :window SECOND)',
            ['email' => strtolower($email), 'ip' => $ip, 'window' => $window]
        );

        return (int) ($row['c'] ?? 0) >= $max;
    }

    public static function clear(string $email, string $ip): void
    {
        Database::execute(
            'DELETE FROM login_attempts WHERE email = :email OR ip = :ip',
            ['email' => strtolower($email), 'ip' => $ip]
        );
    }
}
