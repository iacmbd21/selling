<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class UserController extends Controller
{
    public function index(): void
    {
        $this->render('admin/users/index', [
            'title' => 'Staff',
            'users' => (new User())->allStaff(),
            'errors' => Session::pull('errors') ?? [],
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $role = (string) ($_POST['role'] ?? '');
        $errors = [];
        if (str_length($name) < 2) {
            $errors['name'] = 'Enter a name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if (!in_array($role, ['manager', 'staff'], true)) {
            $errors['role'] = 'Choose manager or staff.';
        }
        if ($errors === [] && (new User())->findByEmail($email)) {
            $errors['email'] = 'That email is already used.';
        }
        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/users');
        }
        (new User())->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
        ]);
        Session::flash('success', 'Staff account created.');
        redirect('/admin/users');
    }
}
