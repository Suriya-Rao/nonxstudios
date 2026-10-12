<?php

declare(strict_types=1);

/**
 * Admin route table. Every controller here (except AuthController) extends
 * AdminController, which enforces the administrator role in its constructor.
 *
 * @var \App\Core\Router $router
 */

use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\InquiryController;
use App\Controllers\Admin\ProjectController;
use App\Controllers\Admin\SettingController;

// --- Authentication -----------------------------------------------------
$router->get('/admin/login',   [AuthController::class, 'showLogin']);
$router->post('/admin/login',  [AuthController::class, 'login']);
$router->post('/admin/logout', [AuthController::class, 'logout']);

// --- Dashboard ----------------------------------------------------------
$router->get('/admin', [DashboardController::class, 'index']);

// --- Projects -----------------------------------------------------------
$router->get('/admin/projects',                 [ProjectController::class, 'index']);
$router->get('/admin/projects/create',          [ProjectController::class, 'create']);
$router->post('/admin/projects',                [ProjectController::class, 'store']);
$router->get('/admin/projects/{id}/edit',       [ProjectController::class, 'edit']);
$router->post('/admin/projects/{id}',           [ProjectController::class, 'update']);
$router->post('/admin/projects/{id}/delete',    [ProjectController::class, 'destroy']);
$router->post('/admin/projects/{id}/images/{imageId}/delete', [ProjectController::class, 'deleteImage']);

// --- Inquiries ----------------------------------------------------------
$router->get('/admin/inquiries',              [InquiryController::class, 'index']);
$router->get('/admin/inquiries/{id}',         [InquiryController::class, 'show']);
$router->post('/admin/inquiries/{id}/status', [InquiryController::class, 'updateStatus']);
$router->post('/admin/inquiries/{id}/notes',  [InquiryController::class, 'updateNotes']);
$router->post('/admin/inquiries/{id}/delete', [InquiryController::class, 'destroy']);

// --- Settings -----------------------------------------------------------
$router->get('/admin/settings',  [SettingController::class, 'index']);
$router->post('/admin/settings', [SettingController::class, 'update']);
