<?php

declare(strict_types=1);

/**
 * NONXSTUDIOS — front controller.
 * All public traffic is routed through this file (see public/.htaccess).
 */

require __DIR__ . '/../bootstrap.php';

use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;

Session::start();
Response::securityHeaders();

$router = new Router();

// Public routes always apply. Admin routes are registered here too so that a
// single document root (`public/`) serves both the site and /admin. The admin
// controllers enforce authentication themselves.
require __DIR__ . '/../routes/web.php';
require __DIR__ . '/../routes/admin.php';

$router->dispatch();
