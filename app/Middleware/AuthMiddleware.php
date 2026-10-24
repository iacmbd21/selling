<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

class AuthMiddleware
{
    public function handle(?string $guard, callable $next): void
    {
        if (!Auth::check()) {
            $target = $guard === 'admin' ? '/admin/login' : '/login';
            Response::redirect(url($target));
        }
        if ($guard === 'admin' && !in_array(Auth::role(), ['admin', 'manager', 'staff'], true)) {
            Response::abort(403, 'You cannot access that page.');
        }
        $next();
    }
}
