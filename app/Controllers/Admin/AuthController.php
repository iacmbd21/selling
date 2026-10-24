<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

class AuthController extends Controller
{
    public function show(): void
    {
        if (Auth::check() && in_array(Auth::role(), ['admin', 'manager', 'staff'], true)) {
            redirect('/admin');
        }
        $this->render('admin/login', ['title' => 'Staff login'], 'layouts/auth');
    }

    public function login(): void
    {
        $ok = Auth::attempt(
            (string) ($_POST['email'] ?? ''),
            (string) ($_POST['password'] ?? ''),
            ['admin', 'manager', 'staff']
        );
        if (!$ok) {
            Session::flash('error', 'Those details do not match a staff account.');
            redirect('/admin/login');
        }
        redirect('/admin');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/admin/login');
    }
}
