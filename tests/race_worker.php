<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Services\OrderService;

$_ENV['DB_NAME'] = 'today_sell_test';
putenv('DB_NAME=today_sell_test');
Database::disconnect();

$database = Database::pdo()->query('SELECT DATABASE()')->fetchColumn();
if ($database !== 'today_sell_test') {
    fwrite(STDERR, "Race worker refused database {$database}\n");
    exit(1);
}

$productId = (int) ($argv[1] ?? 0);
$go = (string) ($argv[2] ?? '');
$done = (string) ($argv[3] ?? '');

$deadline = time() + 20;
while (!is_file($go)) {
    if (time() > $deadline) {
        file_put_contents($done, 'fail timed out waiting to start');
        exit(0);
    }
    usleep(20000);
}

try {
    $order = (new OrderService())->place([
        'customer_name' => 'Race Buyer',
        'mobile' => '01711111111',
        'address' => 'House 1, Road 2, Dhaka',
        'items' => [['product_id' => $productId, 'quantity' => 1]],
    ], null);
    file_put_contents($done, 'ok ' . $order['order_no']);
} catch (Throwable $exception) {
    file_put_contents($done, 'fail ' . $exception->getMessage());
}
