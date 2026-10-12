<?php

declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'NONXSTUDIOS'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => (bool) env('APP_DEBUG', false),
    'url'      => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'session' => [
        'name'     => env('SESSION_NAME', 'nonx_session'),
        'lifetime' => (int) env('SESSION_LIFETIME', 7200),
        'secure'   => (bool) env('SESSION_SECURE', false),
    ],

    'uploads' => [
        'max_bytes' => (int) env('UPLOAD_MAX_BYTES', 8_388_608),
        'allowed'   => ['image/jpeg', 'image/png', 'image/webp', 'image/avif'],
        'dir'       => storage_path('uploads'),
    ],

    'security' => [
        'login_max_attempts'   => (int) env('LOGIN_MAX_ATTEMPTS', 5),
        'login_lockout_window' => (int) env('LOGIN_LOCKOUT_WINDOW', 900),
        'contact_rate_limit'   => (int) env('CONTACT_RATE_LIMIT', 3),
        'contact_rate_window'  => (int) env('CONTACT_RATE_WINDOW', 900),
    ],

    'mail' => [
        'enabled'    => (bool) env('MAIL_ENABLED', false),
        'host'       => env('MAIL_HOST', ''),
        'port'       => (int) env('MAIL_PORT', 587),
        'username'   => env('MAIL_USERNAME', ''),
        'password'   => env('MAIL_PASSWORD', ''),
        'encryption' => env('MAIL_ENCRYPTION', 'tls'),
        'from'       => env('MAIL_FROM_ADDRESS', ''),
        'from_name'  => env('MAIL_FROM_NAME', 'NONXSTUDIOS'),
        'to'         => env('MAIL_TO_ADDRESS', ''),
    ],
];
