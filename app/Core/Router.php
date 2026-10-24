<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<int, array{method: string, regex: string, handler: array{0: class-string, 1: string}, middleware: array<int, string>}> */
    private array $routes = [];

    public function get(string $path, array $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    private function add(string $method, string $path, array $handler, array $middleware): void
    {
        $pattern = preg_replace('#\{([A-Za-z_][A-Za-z0-9_]*)\}#', '(?P<$1>[^/]+)', $path);
        $this->routes[] = [
            'method' => $method,
            'regex' => '#^' . $pattern . '$#',
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $prefix = base_href();
        if ($prefix !== '' && str_starts_with($path, $prefix)) {
            $path = substr($path, strlen($prefix)) ?: '/';
        }
        $path = '/' . trim($path, '/');
        if ($path !== '/') {
            $path = rtrim($path, '/') ?: '/';
        }

        if ($method === 'POST' && !Csrf::verify(isset($_POST['_token']) ? (string) $_POST['_token'] : null)) {
            Response::abort(419, 'Invalid security token. Please try again.');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[] = $value;
                }
            }
            $next = function () use ($route, $params) {
                [$class, $action] = $route['handler'];
                $controller = new $class();
                $controller->{$action}(...$params);
            };
            foreach (array_reverse($route['middleware']) as $middleware) {
                $current = $next;
                $next = function () use ($middleware, $current) {
                    $this->runMiddleware($middleware, $current);
                };
            }
            $next();
            return;
        }

        Response::abort(404, 'That page does not exist.');
    }

    private function runMiddleware(string $middleware, callable $next): void
    {
        $argument = null;
        $class = $middleware;
        if (str_contains($middleware, ':')) {
            [$class, $argument] = explode(':', $middleware, 2);
        }
        (new $class())->handle($argument, $next);
    }
}
