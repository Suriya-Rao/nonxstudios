<?php

declare(strict_types=1);

return [
    'driver'  => 'mysql',
    'host'    => env('DB_HOST', '127.0.0.1'),
    'port'    => (int) env('DB_PORT', 3306),
    'name'    => env('DB_NAME', 'nonxstudios'),
    'user'    => env('DB_USER', 'root'),
    'pass'    => env('DB_PASS', ''),
    'charset' => env('DB_CHARSET', 'utf8mb4'),
];
