<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\User;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        $this->render('auth/login', ['title' => 'Customer login']);
    }

    public function login(): void
    {
        $ok = Auth::attempt((string) ($_POST['email'] ?? ''), (string) ($_POST['password'] ?? ''), ['customer']);
        if (!$ok) {
            Session::flash('error', 'Those details do not match a customer account.');
            Session::flash('old', $_POST);
            redirect('/login');
        }
        redirect('/account/orders');
    }

    public function showRegister(): void
    {
        $this->render('auth/register', ['title' => 'Create account', 'errors' => Session::pull('errors') ?? []]);
    }

    public function register(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $mobile = trim((string) ($_POST['mobile'] ?? ''));
        $errors = [];
        if (str_length($name) < 2) {
            $errors['name'] = 'Enter your name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if ($errors === [] && (new User())->findByEmail($email)) {
            $errors['email'] = 'That email is already registered.';
        }
        if ($errors !== []) {
            Session::flash('errors', $errors);
            Session::flash('old', $_POST);
            redirect('/register');
        }
        (new User())->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'mobile' => $mobile !== '' ? $mobile : null,
            'role' => 'customer',
        ]);
        Auth::attempt($email, $password, ['customer']);
        redirect('/order');
    }

    public function showForgot(): void
    {
        $this->render('auth/forgot', ['title' => 'Reset password', 'resetUrl' => Session::pull('reset_url')]);
    }

    public function forgot(): void
    {
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $user = (new User())->findByEmail($email);
        $resetUrl = null;
        if ($user && $user['role'] === 'customer' && $user['status'] === 'active') {
            $token = bin2hex(random_bytes(32));
            Database::pdo()->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (?, ?, ?)')->execute([
                $email,
                hash('sha256', $token),
                date('Y-m-d H:i:s', time() + 3600),
            ]);
            $resetUrl = url('/reset-password?token=' . $token);
        }
        Session::flash('success', 'If that customer account exists, the reset link is ready.');
        if (env('APP_DEBUG') === 'true') {
            Session::flash('reset_url', $resetUrl);
        }
        redirect('/forgot-password');
    }

    public function showReset(): void
    {
        $this->render('auth/reset', ['title' => 'Choose a new password', 'token' => (string) ($_GET['token'] ?? '')]);
    }

    public function reset(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        if (strlen($password) < 8 || $token === '') {
            Session::flash('error', 'Use a valid reset link and a password of at least 8 characters.');
            redirect('/forgot-password');
        }
        $statement = Database::pdo()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at >= ? LIMIT 1');
        $statement->execute([hash('sha256', $token), date('Y-m-d H:i:s')]);
        $reset = $statement->fetch();
        $user = $reset ? (new User())->findByEmail((string) $reset['email']) : null;
        if (!$reset || !$user) {
            Session::flash('error', 'That reset link has expired.');
            redirect('/forgot-password');
        }
        (new User())->updatePassword((int) $user['id'], $password);
        Database::pdo()->prepare('UPDATE password_resets SET used_at = ? WHERE id = ?')->execute([date('Y-m-d H:i:s'), (int) $reset['id']]);
        Session::flash('success', 'Password updated. You can log in.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/');
    }
}
