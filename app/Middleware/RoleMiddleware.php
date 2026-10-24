<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Gate;
use App\Core\Response;

class RoleMiddleware
{
    public function handle(?string $permission, callable $next): void
    {
        if (!$permission || !Gate::allows(Auth::role(), $permission)) {
            Response::abort(403, 'You cannot access that page.');
        }
        $next();
    }
}
