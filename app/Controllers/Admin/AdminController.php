<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

/**
 * Shared base for every admin controller. Enforces the administrator role on
 * construction, so no admin action can be reached without a valid session.
 */
abstract class AdminController
{
    protected array $admin;

    public function __construct()
    {
        Auth::requireAdmin();
        $this->admin = Auth::user() ?? [];
    }

    protected function view(string $view, array $data = []): void
    {
        $data['admin'] = $this->admin;
        echo View::render($view, $data, 'layouts/admin');
    }

    /**
     * Verify CSRF and that the request is a POST; aborts otherwise.
     */
    protected function verifyPost(): void
    {
        if (!Request::isPost()) {
            Response::abort(405, 'This action requires a POST request.');
        }
        \App\Core\Csrf::validateOrFail(Request::input('_token'));
    }

    /** Redirect back to a list page with a flash message. */
    protected function back(string $path, string $type, string $message): never
    {
        \App\Core\Session::flash($type, $message);
        Response::redirect($path);
    }
}
