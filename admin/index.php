<?php

declare(strict_types=1);

/**
 * Admin front controller.
 *
 * Served at /admin. When the document root is `public/` (recommended) this
 * directory is outside the web root and is reached via the alias rule in
 * public/.htaccess. If instead the document root is the project root, this
 * file is the entry point directly.
 */

require __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

Session::start();
Response::securityHeaders();

$router = new Router();

require __DIR__ . '/../routes/admin.php';

$router->dispatch();
