<?php

declare(strict_types=1);

/**
 * Application bootstrap: autoloading, environment, error handling, timezone.
 * Shared by the public front controller and CLI scripts (seeder, admin tools).
 */

if (defined('NONX_BOOTSTRAPPED')) {
    return;
}
define('NONX_BOOTSTRAPPED', true);
define('NONX_BASE_PATH', __DIR__);

// --- Autoloading -----------------------------------------------------------
$composer = __DIR__ . '/vendor/autoload.php';
if (is_readable($composer)) {
    require $composer;
} else {
    // Fallback PSR-4 autoloader so the app runs before `composer install`.
    spl_autoload_register(static function (string $class): void {
        $prefix = 'App\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $file = __DIR__ . '/app/' . str_replace('\\', '/', $relative) . '.php';
        if (is_readable($file)) {
            require $file;
        }
    });
    require __DIR__ . '/app/Helpers/functions.php';
}

use App\Core\Env;
use App\Core\Logger;
use App\Core\Response;

// --- Environment -----------------------------------------------------------
Env::load(__DIR__ . '/.env');

date_default_timezone_set((string) env('APP_TIMEZONE', 'UTC'));

// --- Error handling --------------------------------------------------------
$debug = (bool) env('APP_DEBUG', false);
ini_set('display_errors', $debug ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    // Deprecations and notices are logged, never fatal.
    if (in_array($severity, [E_DEPRECATED, E_USER_DEPRECATED, E_NOTICE, E_USER_NOTICE, E_WARNING, E_USER_WARNING], true)) {
        Logger::log('warning', $message, ['file' => $file, 'line' => $line]);
        return true;
    }
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (\Throwable $e) use ($debug): void {
    Logger::error($e->getMessage(), [
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $debug ? $e->getTraceAsString() : null,
    ]);

    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Error: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}\n");
        exit(1);
    }

    if (!headers_sent()) {
        http_response_code(500);
    }

    if ($debug) {
        echo '<pre style="background:#111;color:#f5f5f5;padding:2rem;font:14px/1.6 monospace;overflow:auto">';
        echo htmlspecialchars((string) $e, ENT_QUOTES), '</pre>';
    } else {
        require __DIR__ . '/app/Views/pages/error.php';
    }
    exit;
});
