<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\HttpException;
use App\Core\Kernel;
use App\Core\RedirectException;
use App\Core\View;

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

try {
    echo (new Kernel())->handle();
} catch (RedirectException $exception) {
    header('Location: ' . $exception->location, true, 302);
    exit;
} catch (HttpException $exception) {
    http_response_code($exception->status);
    $template = match ($exception->status) {
        403 => 'errors/403',
        419 => 'errors/419',
        default => 'errors/404',
    };
    echo View::render($template, [
        'title' => (string) $exception->status,
        'message' => $exception->getMessage(),
    ]);
} catch (Throwable $exception) {
    $logDir = BASE_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0775, true);
    }
    file_put_contents(
        $logDir . '/app.log',
        date('c') . ' ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine() . PHP_EOL,
        FILE_APPEND
    );
    http_response_code(500);
    if (env('APP_DEBUG') === 'true') {
        echo '<h1>Application error</h1><pre>' . e($exception->getMessage()) . '</pre>';
    } else {
        echo 'Something went wrong.';
    }
}
