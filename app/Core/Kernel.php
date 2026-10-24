<?php

declare(strict_types=1);

namespace App\Core;

class Kernel
{
    public function handle(): string
    {
        $router = new Router();
        require BASE_PATH . '/routes/web.php';
        require BASE_PATH . '/routes/admin.php';

        ob_start();
        try {
            $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $router->dispatch($method, $uri);
            return (string) ob_get_clean();
        } catch (\Throwable $exception) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $exception;
        }
    }
}
