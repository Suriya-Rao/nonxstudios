<?php

declare(strict_types=1);

/**
 * Route table. Included by public/index.php and admin/index.php.
 * @var \App\Core\Router $router
 */

use App\Controllers\ConsentController;
use App\Controllers\ContactController;
use App\Controllers\HomeController;
use App\Controllers\MediaController;
use App\Controllers\SeoController;
use App\Controllers\WorkController;

// --- Home ---------------------------------------------------------------
$router->get('/', [HomeController::class, 'index']);

// --- Work / portfolio ---------------------------------------------------
$router->get('/work', [WorkController::class, 'index']);
$router->get('/project/{slug}', [WorkController::class, 'show']);

// --- Static marketing pages --------------------------------------------
$router->get('/services', [HomeController::class, 'services']);
$router->get('/about',    [HomeController::class, 'about']);
$router->get('/faq',      [HomeController::class, 'faq']);
$router->get('/privacy',  [HomeController::class, 'privacy']);
$router->get('/terms',    [HomeController::class, 'terms']);

// --- Contact ------------------------------------------------------------
$router->get('/contact',  [ContactController::class, 'show']);
$router->post('/contact', [ContactController::class, 'submit']);

// --- Cookie consent (analytics opt-in) ----------------------------------
$router->post('/consent', [ConsentController::class, 'store']);

// --- Media (streamed from storage/, never executed) ---------------------
$router->get('/media/{path...}', [MediaController::class, 'show']);

// --- SEO ----------------------------------------------------------------
$router->get('/sitemap.xml', [SeoController::class, 'sitemap']);
$router->get('/robots.txt',  [SeoController::class, 'robots']);
