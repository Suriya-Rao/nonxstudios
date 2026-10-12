<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Admin sign-in / sign-out. This controller deliberately does NOT extend
 * AdminController (which would require an authenticated session).
 */
final class AuthController
{
    public function showLogin(): void
    {
        // Already signed in? Go straight to the dashboard.
        if (Auth::check() && Auth::isAdmin()) {
            Response::redirect('/admin');
        }

        echo View::render('admin/login', [
            'old'    => Session::pull('_old', []),
            'errors' => Session::pull('_errors', []),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        Csrf::validateOrFail(Request::input('_token'));

        $email    = Request::string('email');
        $password = (string) Request::input('password', '');

        if ($email === '' || $password === '') {
            Session::put('_old', ['email' => $email]);
            Session::put('_errors', ['email' => 'Enter your email and password.']);
            Response::redirect('/admin/login');
        }

        $result = Auth::attempt($email, $password, Request::ip());

        if (!$result['ok']) {
            Session::put('_old', ['email' => $email]);
            Session::put('_errors', ['email' => $result['error']]);
            Session::flash('error', $result['error']);
            Response::redirect('/admin/login');
        }

        Session::flash('success', 'Welcome back.');
        Response::redirect('/admin');
    }

    public function logout(): void
    {
        Csrf::validateOrFail(Request::input('_token'));
        Auth::logout();
        Session::flash('success', 'You have been signed out.');
        Response::redirect('/admin/login');
    }
}
