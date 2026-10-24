<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function abort(int $status, string $message = ''): never
    {
        throw new HttpException($status, $message);
    }

    public static function redirect(string $location): never
    {
        throw new RedirectException($location);
    }
}
