<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Auth;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Kernel;
use App\Core\Money;
use App\Core\RedirectException;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\BackupService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ProductImageService;
use App\Services\ReportService;

$_ENV['DB_NAME'] = 'today_sell_test';
putenv('DB_NAME=today_sell_test');
Database::disconnect();

$failures = 0;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function test(string $name, callable $fn): void
{
    global $failures;
    try {
        $fn();
        echo "PASS {$name}\n";
    } catch (Throwable $exception) {
        $failures++;
        echo "FAIL {$name}\n{$exception->getMessage()}\n{$exception->getFile()}:{$exception->getLine()}\n";
    }
}

function use_test_database(): void
{
    $server = Database::server();
    $server->exec('DROP DATABASE IF EXISTS today_sell_test');
    $server->exec('CREATE DATABASE today_sell_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    Database::disconnect();
    $pdo = Database::pdo();
    $name = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if ($name !== 'today_sell_test') {
        throw new RuntimeException('Test connected to ' . $name);
    }
    foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [] as $file) {
        $sql = file_get_contents($file);
        foreach (preg_split('/;\s*\R/', (string) $sql) ?: [] as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }
    }
}

function make_category(string $name): int
{
    return (new Category())->create($name, slugify($name) . '-' . bin2hex(random_bytes(2)));
}

function make_product(int $categoryId, string $name, string $price, int $stock, string $status = 'active'): int
{
    $id = (new Product())->create([
        'category_id' => $categoryId,
        'name' => $name,
        'slug' => slugify($name) . '-' . bin2hex(random_bytes(2)),
        'sku' => strtoupper(bin2hex(random_bytes(4))),
        'description' => $name . ' description',
        'price' => Money::normalize($price),
        'stock' => $stock,
        'status' => $status,
    ]);
    (new InventoryMovement())->record(null, $id, 'adjustment', $stock, $stock, 'Opening stock');
    return $id;
}

function buyer(int $productId, int $quantity = 1, array $extra = []): array
{
    return $extra + [
        'customer_name' => 'Karim Uddin',
        'mobile' => '01712345678',
        'address' => 'House 12, Road 4, Dhaka',
        'items' => [['product_id' => $productId, 'quantity' => $quantity]],
        'price' => '1.00',
        'total' => '1.00',
        'payment_status' => 'paid',
    ];
}

function stock_of(int $productId): int
{
    $statement = Database::pdo()->prepare('SELECT stock FROM products WHERE id = ?');
    $statement->execute([$productId]);
    return (int) $statement->fetchColumn();
}

function http(string $method, string $path, array $post = []): string
{
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = $path;
    $_POST = $post;
    $_GET = [];
    $query = parse_url($path, PHP_URL_QUERY);
    if (is_string($query)) {
        parse_str($query, $_GET);
    }
    return (new Kernel())->handle();
}

use_test_database();

test('T01 active products can be browsed, searched, and filtered', function (): void {
    $grocery = make_category('Grocery');
    $fashion = make_category('Fashion');
    $apple = make_product($grocery, 'Alpha Unique Rice', '100.00', 5);
    $beta = make_product($fashion, 'Beta Unique Panjabi', '200.00', 5);
    $hidden = make_product($grocery, 'Hidden Unique Lamp', '50.00', 5, 'inactive');
    $products = new Product();

    $all = $products->search([]);
    $names = array_column($all, 'name');
    check(in_array('Alpha Unique Rice', $names, true), 'Active product missing from browse');
    check(!in_array('Hidden Unique Lamp', $names, true), 'Inactive product was visible');

    $found = $products->search(['q' => 'Alpha Unique']);
    check(count($found) === 1 && $found[0]['name'] === 'Alpha Unique Rice', 'Search did not find the product');

    $injected = $products->search(['q' => "' OR 1=1 --"]);
    check($injected === [], 'Search injection changed the result');

    $filtered = $products->search(['category_id' => $fashion, 'min_price' => '150', 'max_price' => '250']);
    check(count($filtered) === 1 && (int) $filtered[0]['id'] === $beta, 'Filter missed the category or price');

    $_GET = ['q' => 'Alpha Unique'];
    $page = http('GET', '/products?q=Alpha+Unique');
    check(str_contains($page, 'Alpha Unique Rice'), 'Catalog page did not show the search result');
    check(!str_contains($page, 'Hidden Unique Lamp'), 'Catalog page showed an inactive product');
    check(!str_contains($page, 'Beta Unique Panjabi'), 'Catalog search returned another product');

    $slug = $products->find($apple)['slug'];
    $detail = http('GET', '/products/' . $slug);
    check(str_contains($detail, 'Alpha Unique Rice'), 'Product page did not render');
    $hiddenSlug = $products->find($hidden)['slug'];
    $missing = false;
    try {
        http('GET', '/products/' . $hiddenSlug);
    } catch (HttpException $exception) {
        $missing = $exception->status === 404;
    }
    check($missing, 'Inactive product page was available');
});

test('T02 guest and registered customers can place an order with name, mobile, and address', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Guest Soap', '80.00', 5);
    $orders = new OrderService();
    $guest = $orders->place(buyer($productId), null);
    check($guest['user_id'] === null, 'Guest order was tied to a user');
    check($guest['customer_name'] === 'Karim Uddin', 'Customer name was not saved');
    check($guest['mobile'] === '01712345678', 'Mobile number was not saved');

    $customerId = (new User())->create([
        'name' => 'Registered Buyer',
        'email' => 'buyer' . bin2hex(random_bytes(2)) . '@todaysell.test',
        'password' => 'password',
        'role' => 'customer',
    ]);
    $registered = $orders->place(buyer($productId, 1, [
        'customer_name' => 'Registered Buyer',
        'mobile' => '01812345678',
        'address' => 'Flat 2, Block C, Dhaka',
    ]), $customerId);
    check((int) $registered['user_id'] === $customerId, 'Registered order lost the customer');

    $form = http('GET', '/order');
    foreach (['Select product', 'Customer name', 'Mobile no', 'Address'] as $label) {
        check(str_contains($form, $label), 'Order form is missing ' . $label);
    }
});

test('T03 server totals match the saved order and invoice', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Priced Rice', '500.00', 10);
    $order = (new OrderService())->place(buyer($productId), null);
    check($order['subtotal'] === '500.00', 'Subtotal was not the database price');
    check($order['discount'] === '0.00', 'Unexpected discount');
    check($order['shipping'] === '60.00', 'Dhaka delivery was not calculated');
    check($order['total'] === '560.00', 'Client total was accepted');
    check($order['items'][0]['unit_price'] === '500.00', 'Client unit price was accepted');
    check($order['payment_status'] === 'pending', 'Client payment status was accepted');

    $invoice = Database::pdo()->prepare('SELECT snapshot_json FROM invoices WHERE order_id = ?');
    $invoice->execute([(int) $order['id']]);
    $snapshot = json_decode((string) $invoice->fetchColumn(), true);
    check($snapshot['total'] === '560.00', 'Invoice total differs from the order');
    check($snapshot['items'][0]['line_total'] === '500.00', 'Invoice line total differs');

    $outside = (new OrderService())->place(buyer(make_product($category, 'Sylhet Tea', '500.00', 4), 1, [
        'address' => 'House 2, Station Road, Sylhet',
    ]), null);
    check($outside['shipping'] === '120.00' && $outside['total'] === '620.00', 'Outside Dhaka delivery was wrong');

    $free = (new OrderService())->place(buyer($productId, 3), null);
    check($free['subtotal'] === '1500.00' && $free['shipping'] === '0.00' && $free['total'] === '1500.00', 'Free delivery threshold was wrong');
});

test('T04 insufficient stock and simultaneous checkout cannot oversell', function (): void {
    $category = make_category('Grocery');
    $short = make_product($category, 'Last Bottle', '40.00', 1);
    $threw = false;
    try {
        (new OrderService())->place(buyer($short, 2), null);
    } catch (DomainException $exception) {
        $threw = str_contains($exception->getMessage(), 'Insufficient stock');
    }
    check($threw, 'Oversized order was accepted');
    check(stock_of($short) === 1, 'Failed order changed stock');

    $race = make_product($category, 'Single Jar', '70.00', 1);
    $dir = BASE_PATH . '/storage/logs/race-' . bin2hex(random_bytes(3));
    mkdir($dir);
    $go = $dir . '/go';
    $done = [$dir . '/a.txt', $dir . '/b.txt'];
    $processes = [];
    foreach ($done as $file) {
        $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(BASE_PATH . '/tests/race_worker.php') . ' ' . $race . ' ' . escapeshellarg($go) . ' ' . escapeshellarg($file);
        $processes[] = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        $processes[array_key_last($processes) . '_pipes'] = $pipes;
    }
    $handles = [];
    foreach ($processes as $key => $process) {
        if (is_int($key)) {
            $handles[] = $process;
        }
    }
    usleep(200000);
    file_put_contents($go, 'go');
    $started = time();
    while (time() - $started < 25 && (!is_file($done[0]) || !is_file($done[1]))) {
        usleep(100000);
    }
    foreach ($handles as $index => $process) {
        if (is_resource($process)) {
            proc_close($process);
        }
    }
    $results = [is_file($done[0]) ? file_get_contents($done[0]) : 'missing', is_file($done[1]) ? file_get_contents($done[1]) : 'missing'];
    $wins = count(array_filter($results, static fn (string $result): bool => str_starts_with($result, 'ok')));
    $count = Database::pdo()->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
    $count->execute([$race]);
    check($wins === 1, 'Simultaneous checkout wins: ' . implode(' | ', $results));
    check((int) $count->fetchColumn() === 1, 'More than one order took the last item');
    check(stock_of($race) === 0, 'Stock was not exactly zero after the race');
});

test('T05 cash orders stay unpaid until collection is recorded', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Cash Rice', '100.00', 5);
    $staff = (new User())->create(['name' => 'Collector', 'email' => 'collector' . bin2hex(random_bytes(2)) . '@todaysell.test', 'password' => 'password', 'role' => 'staff']);
    $order = (new OrderService())->place(buyer($productId), null);
    check($order['payment_method'] === 'cod' && $order['payment_status'] === 'pending', 'New order was not unpaid cash');
    $paid = Database::pdo()->prepare("SELECT COUNT(*) FROM payments WHERE order_id = ? AND status = 'verified'");
    $paid->execute([(int) $order['id']]);
    check((int) $paid->fetchColumn() === 0, 'Uncollected order already had a paid row');

    $payments = new PaymentService();
    $payments->collectCod((int) $order['id'], $staff, (string) $order['total']);
    $fresh = Database::pdo()->prepare('SELECT payment_status FROM orders WHERE id = ?');
    $fresh->execute([(int) $order['id']]);
    check($fresh->fetchColumn() === 'verified', 'Collection did not mark the order paid');
    $again = false;
    try {
        $payments->collectCod((int) $order['id'], $staff, (string) $order['total']);
    } catch (DomainException) {
        $again = true;
    }
    check($again, 'Cash was collected twice');
});

test('T06 bKash and Nagad submissions require authorized verification', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Mobile Pay Rice', '100.00', 8);
    $orders = new OrderService();
    $payments = new PaymentService();
    $staff = (new User())->create(['name' => 'Submitter', 'email' => 'submit' . bin2hex(random_bytes(2)) . '@todaysell.test', 'password' => 'password', 'role' => 'staff']);
    $manager = (new User())->create(['name' => 'Verifier', 'email' => 'verify' . bin2hex(random_bytes(2)) . '@todaysell.test', 'password' => 'password', 'role' => 'manager']);
    $order = $orders->place(buyer($productId), null);

    $bad = false;
    try {
        $payments->submitMobile((int) $order['id'], 'bkash', 'BAD', '01712345678', (string) $order['total'], $staff);
    } catch (DomainException $exception) {
        $bad = str_contains($exception->getMessage(), 'transaction');
    }
    check($bad, 'Invalid transaction was accepted');

    $amount = false;
    try {
        $payments->submitMobile((int) $order['id'], 'bkash', 'BKASH12345', '01712345678', '1.00', $staff);
    } catch (DomainException $exception) {
        $amount = str_contains($exception->getMessage(), 'amount');
    }
    check($amount, 'Wrong amount was accepted');

    $payments->submitMobile((int) $order['id'], 'bkash', 'BKASH12345', '01712345678', (string) $order['total'], $staff);
    $pending = Database::pdo()->prepare('SELECT * FROM payments WHERE order_id = ? AND status = ?');
    $pending->execute([(int) $order['id'], 'submitted']);
    $payment = $pending->fetch();
    check($payment !== false, 'Valid submission was not stored');

    $staffBlocked = false;
    try {
        $payments->verify((int) $payment['id'], $staff);
    } catch (DomainException) {
        $staffBlocked = true;
    }
    check($staffBlocked, 'Staff verified their own submission');

    $self = false;
    try {
        $payments->verify((int) $payment['id'], $staff);
    } catch (DomainException) {
        $self = true;
    }
    check($self, 'Submitter verification was not rejected');

    $payments->verify((int) $payment['id'], $manager);
    $status = Database::pdo()->prepare('SELECT payment_status FROM orders WHERE id = ?');
    $status->execute([(int) $order['id']]);
    check($status->fetchColumn() === 'verified', 'Authorized verification did not mark the order paid');

    $second = $orders->place(buyer($productId), null);
    $duplicate = false;
    try {
        $payments->submitMobile((int) $second['id'], 'bkash', 'BKASH12345', '01712345678', (string) $second['total'], $staff);
    } catch (DomainException) {
        $duplicate = true;
    }
    check($duplicate, 'A used transaction ID was accepted');

    $nagad = $orders->place(buyer($productId), null);
    $invalidNagad = false;
    try {
        $payments->submitMobile((int) $nagad['id'], 'nagad', 'NAGAD-1', '12345', (string) $nagad['total'], $staff);
    } catch (DomainException) {
        $invalidNagad = true;
    }
    check($invalidNagad, 'Invalid Nagad submission was accepted');
});

test('T07 invalid, expired, and overused coupons are rejected', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Coupon Rice', '500.00', 10);
    $coupons = new Coupon();
    $orders = new OrderService();
    $coupons->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => 5, 'starts_at' => null, 'expires_at' => null]);
    $coupons->create(['code' => 'EXPIRED10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => null, 'starts_at' => null, 'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
    $coupons->create(['code' => 'SOON10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => null, 'starts_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'expires_at' => null]);
    $coupons->create(['code' => 'ONCE10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => 1, 'used_count' => 0, 'starts_at' => null, 'expires_at' => null]);
    $coupons->create(['code' => 'NEED1000', 'type' => 'fixed', 'value' => '10.00', 'min_order' => '1000.00', 'max_uses' => null, 'starts_at' => null, 'expires_at' => null]);

    $reject = function (string $code) use ($orders, $productId): bool {
        try {
            $orders->place(buyer($productId, 1, ['coupon_code' => $code]), null);
            return false;
        } catch (DomainException) {
            return true;
        }
    };
    check($reject('NOTREAL'), 'Invalid coupon was accepted');
    check($reject('EXPIRED10'), 'Expired coupon was accepted');
    check($reject('SOON10'), 'Future coupon was accepted');
    check($reject('NEED1000'), 'Coupon below the minimum was accepted');

    $saved = $orders->place(buyer($productId, 1, ['coupon_code' => 'SAVE10']), null);
    check($saved['discount'] === '50.00' && $saved['total'] === '510.00', 'Valid percent coupon calculated the wrong total');
    $first = $orders->place(buyer($productId, 1, ['coupon_code' => 'ONCE10', 'mobile' => '01612345678']), null);
    check($first['discount'] === '50.00', 'Single-use coupon did not apply');
    check($reject('ONCE10'), 'Overused coupon was accepted');
});

test('T08 cancellation and returns restore stock once', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Returnable Oil', '100.00', 8);
    $orders = new OrderService();
    $cancelled = $orders->place(buyer($productId, 2), null);
    check(stock_of($productId) === 6, 'Sale did not reduce stock');
    $orders->cancel((int) $cancelled['id']);
    check(stock_of($productId) === 8, 'Cancel did not restore stock');
    $second = false;
    try {
        $orders->cancel((int) $cancelled['id']);
    } catch (DomainException) {
        $second = true;
    }
    check($second && stock_of($productId) === 8, 'Cancelling twice changed stock');

    $returned = $orders->place(buyer($productId, 2, ['mobile' => '01912345678']), null);
    $orders->confirm((int) $returned['id']);
    $orders->process((int) $returned['id']);
    $orders->ship((int) $returned['id'], 'Store courier', 'TRK100');
    $orders->deliver((int) $returned['id']);
    check(stock_of($productId) === 6, 'Completed order stock was wrong');
    $orders->returnOrder((int) $returned['id']);
    check(stock_of($productId) === 8, 'Return did not restore stock');
    $again = false;
    try {
        $orders->returnOrder((int) $returned['id']);
    } catch (DomainException) {
        $again = true;
    }
    check($again && stock_of($productId) === 8, 'Returning twice changed stock');
});

test('T09 staff cannot open unauthorized pages or verify payments', function (): void {
    $staff = (new User())->create(['name' => 'Counter', 'email' => 'counter' . bin2hex(random_bytes(2)) . '@todaysell.test', 'password' => 'password', 'role' => 'staff']);
    $_SESSION['user_id'] = $staff;
    $_SESSION['_csrf'] = 'csrf-test-token';
    Auth::forget();

    foreach (['/admin/users', '/admin/backup', '/admin/reports', '/admin/products', '/admin/coupons'] as $path) {
        $blocked = false;
        try {
            http('GET', $path);
        } catch (HttpException $exception) {
            $blocked = $exception->status === 403;
        }
        check($blocked, $path . ' was visible to staff');
    }

    $orders = http('GET', '/admin/orders');
    check(str_contains($orders, 'Orders'), 'Staff could not open orders');

    $verifyBlocked = false;
    try {
        http('POST', '/admin/payments/1/verify', ['_token' => 'csrf-test-token']);
    } catch (HttpException $exception) {
        $verifyBlocked = $exception->status === 403;
    }
    check($verifyBlocked, 'Staff could open payment verification');
    Auth::logout();
});

test('T10 invoices keep historical product names and prices', function (): void {
    $category = make_category('Grocery');
    $productId = make_product($category, 'Historic Rice', '500.00', 5);
    $order = (new OrderService())->place(buyer($productId), null);
    Database::pdo()->prepare('UPDATE products SET name = ?, price = ? WHERE id = ?')->execute(['Renamed Rice', '999.00', $productId]);
    $invoice = (new \App\Services\InvoiceService())->findByOrder((int) $order['id']);
    $html = (new \App\Services\InvoiceService())->render($invoice);
    check(str_contains($html, 'Historic Rice'), 'Invoice lost the original product name');
    check(str_contains($html, '500.00') || str_contains($html, '৳500.00'), 'Invoice lost the original price');
    check(!str_contains($html, 'Renamed Rice'), 'Invoice picked up a later product name');
    check(!str_contains($html, '999.00'), 'Invoice picked up a later price');
    $stored = BASE_PATH . '/storage/invoices/' . $order['invoice_no'] . '.html';
    check(is_file($stored) && str_contains((string) file_get_contents($stored), 'Historic Rice'), 'Stored invoice lost the original product');
});

test('T11 sales, payments, and inventory reconcile', function (): void {
    $report = (new ReportService())->reconcile();
    check($report['ok'] === true, 'Reconciliation failed: ' . implode('; ', $report['issues']));

    $row = Database::pdo()->query('SELECT id, total FROM orders ORDER BY id DESC LIMIT 1')->fetch();
    Database::pdo()->prepare('UPDATE orders SET total = ? WHERE id = ?')->execute(['1.00', (int) $row['id']]);
    $broken = (new ReportService())->reconcile();
    check($broken['ok'] === false, 'A tampered total still reconciled');
    Database::pdo()->prepare('UPDATE orders SET total = ? WHERE id = ?')->execute([$row['total'], (int) $row['id']]);
    $restored = (new ReportService())->reconcile();
    check($restored['ok'] === true, 'Restored records did not reconcile');
});

test('T12 backup restoration and security checks succeed', function (): void {
    check(session_get_cookie_params()['httponly'] === true, 'Session cookie is not HTTP only');
    $user = Database::pdo()->query('SELECT password FROM users LIMIT 1')->fetch();
    check(is_string($user['password']) && $user['password'] !== 'password' && password_verify('password', $user['password']), 'Password was not hashed');

    $token = false;
    try {
        http('POST', '/order', ['customer_name' => 'No Token']);
    } catch (HttpException $exception) {
        $token = $exception->status === 419;
    }
    check($token, 'Missing security token was accepted');

    $backup = new BackupService();
    Database::pdo()->prepare('INSERT INTO app_meta (meta_key, meta_value) VALUES (?, ?)')->execute(['marker', 'original']);
    $dump = $backup->dump();
    Database::pdo()->prepare('UPDATE app_meta SET meta_value = ? WHERE meta_key = ?')->execute(['changed', 'marker']);
    $backup->restore($dump);
    $value = Database::pdo()->prepare('SELECT meta_value FROM app_meta WHERE meta_key = ?');
    $value->execute(['marker']);
    check($value->fetchColumn() === 'original', 'Backup restore did not return the original value');

    $refused = false;
    try {
        $backup->restore("DROP DATABASE today_sell_test;\n");
    } catch (DomainException) {
        $refused = true;
    }
    check($refused, 'Hostile backup statement was executed');
    check(Database::pdo()->query('SELECT DATABASE()')->fetchColumn() === 'today_sell_test', 'Security test left the database');
});

test('T13 product photos can be stored and managed', function (): void {
    $images = new ProductImageService();
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $path = $images->storeBytes((string) $png);
    check(is_file(BASE_PATH . '/public/' . $path), 'Product image was not saved');

    $rejected = false;
    try {
        $images->storeBytes('<?php echo "no";');
    } catch (DomainException) {
        $rejected = true;
    }
    check($rejected, 'A script was stored as a product image');

    $localFile = false;
    try {
        $images->store(['error' => UPLOAD_ERR_OK, 'tmp_name' => BASE_PATH . '/public/index.php', 'size' => 20]);
    } catch (DomainException) {
        $localFile = true;
    }
    check($localFile, 'A file already on disk was accepted as an upload');
    $images->delete('../.env');
    $images->delete('uploads/products/../../../public/index.php');
    check(is_file(BASE_PATH . '/public/index.php'), 'Image cleanup removed a shop file');

    $category = make_category('Photos');
    $id = (new Product())->create([
        'category_id' => $category,
        'name' => 'Photo Rice',
        'slug' => 'photo-rice-' . bin2hex(random_bytes(2)),
        'sku' => strtoupper(bin2hex(random_bytes(4))),
        'description' => 'Rice with a photo',
        'price' => Money::normalize('100.00'),
        'stock' => 3,
        'status' => 'active',
        'featured' => 1,
        'image_path' => $path,
    ]);
    $secondPath = $images->storeBytes((string) $png);
    (new ProductImage())->add($id, $path, 0);
    (new ProductImage())->add($id, $secondPath, 1);
    $saved = (new Product())->find($id);
    check($saved && (int) $saved['featured'] === 1 && $saved['image_path'] === $path, 'Product did not keep its photo');
    check(count((new ProductImage())->forProduct($id)) === 2, 'Product did not keep both photos');

    $firstPage = (new Product())->paginate([], 1, Product::PAGE_SIZE, false);
    $nextPage = (new Product())->paginate([], 2, Product::PAGE_SIZE, false);
    check($firstPage['pages'] > 1 && count($firstPage['items']) === Product::PAGE_SIZE, 'Product list did not paginate');
    check($nextPage['items'] !== [] && (int) $firstPage['items'][0]['id'] !== (int) $nextPage['items'][0]['id'], 'The next page repeated the first page');
    $clamped = (new Product())->paginate([], 999, Product::PAGE_SIZE, false);
    check($clamped['page'] === $clamped['pages'], 'A high page number was not limited');

    $tooMany = false;
    try {
        $images->storeUploads([
            'name' => array_fill(0, 7, 'a.png'),
            'type' => array_fill(0, 7, 'image/png'),
            'tmp_name' => array_fill(0, 7, 'x'),
            'error' => array_fill(0, 7, UPLOAD_ERR_OK),
            'size' => array_fill(0, 7, 10),
        ], ProductImageService::MAX_PHOTOS);
    } catch (DomainException) {
        $tooMany = true;
    }
    check($tooMany, 'More than 6 photos were accepted');

    $admin = (new User())->create(['name' => 'Photo Admin', 'email' => 'photos' . bin2hex(random_bytes(2)) . '@todaysell.test', 'password' => 'password', 'role' => 'admin']);
    $_SESSION['user_id'] = $admin;
    $_SESSION['_csrf'] = 'csrf-test-token';
    Auth::forget();
    $page = http('GET', '/admin/products');
    check(str_contains($page, 'enctype="multipart/form-data"') && str_contains($page, 'name="images[]"') && str_contains($page, 'multiple'), 'Product manage page cannot select multiple photos');
    check(str_contains($page, 'data-open-modal="add-product"') && str_contains($page, '<dialog id="add-product"'), 'Add product form is not in a modal');
    check(str_contains($page, 'Product pages'), 'Product list has no pagination');
    check(str_contains($page, 'Photo Rice'), 'Manage page hid the newest product');
    $edit = http('GET', '/admin/products/' . $id . '/edit');
    check(str_contains($edit, 'Save product') && str_contains($edit, basename($path)) && str_contains($edit, basename($secondPath)), 'Edit page did not show the uploaded photos');

    $imageIds = Database::pdo()->prepare('SELECT id FROM product_images WHERE product_id = ? ORDER BY sort_order, id');
    $imageIds->execute([$id]);
    $removeIds = array_map(static fn (mixed $value): string => (string) $value, $imageIds->fetchAll(PDO::FETCH_COLUMN));
    $redirected = false;
    try {
        http('POST', '/admin/products/' . $id, [
            '_token' => 'csrf-test-token',
            'name' => 'Photo Rice',
            'sku' => $saved['sku'],
            'price' => '100.00',
            'category_id' => (string) $category,
            'description' => 'Rice with a photo',
            'status' => 'active',
            'remove_images' => $removeIds,
        ]);
    } catch (RedirectException $exception) {
        $redirected = str_ends_with($exception->location, '/admin/products');
    }
    $after = (new Product())->find($id);
    check($redirected && $after && $after['image_path'] === null && !is_file(BASE_PATH . '/public/' . $path) && !is_file(BASE_PATH . '/public/' . $secondPath), 'Removing the photos left them in place');
    $byName = (new Product())->paginate(['name' => 'Photo Rice', 'sku' => $saved['sku']], 1, Product::PAGE_SIZE, false);
    check(count($byName['items']) === 1 && $byName['items'][0]['name'] === 'Photo Rice', 'Column filters did not find Photo Rice');
    $filteredPage = http('GET', '/admin/products?name=' . rawurlencode('Photo Rice'));
    check(str_contains($filteredPage, 'Photo Rice') && str_contains($filteredPage, 'aria-label="Edit Photo Rice"') && str_contains($filteredPage, 'aria-label="Delete Photo Rice"'), 'Product list is missing column filters or action icons');
    $deleted = false;
    try {
        http('POST', '/admin/products/' . $id . '/delete', ['_token' => 'csrf-test-token']);
    } catch (RedirectException $exception) {
        $deleted = str_ends_with($exception->location, '/admin/products');
    }
    check($deleted && (new Product())->find($id) === null, 'Delete did not remove the product');
    Auth::logout();
});

test('T14 stock and totals follow the product unit', function (): void {
    $category = make_category('Weights');
    $id = (new Product())->create([
        'category_id' => $category,
        'name' => 'Loose Rice',
        'slug' => 'loose-rice-' . bin2hex(random_bytes(2)),
        'sku' => strtoupper(bin2hex(random_bytes(4))),
        'description' => 'Rice sold by the kilogram',
        'price' => Money::normalize('100.00'),
        'stock' => 10,
        'unit' => 'kg',
        'status' => 'active',
    ]);
    (new InventoryMovement())->record(null, $id, 'adjustment', 10, 10, 'Opening stock');
    $order = (new OrderService())->place(buyer($id, 2), null);
    check(stock_of($id) === 8, 'Kilogram stock was not reduced by the ordered quantity');
    $items = (new \App\Models\Order())->items((int) $order['id']);
    check(count($items) === 1 && $items[0]['unit'] === 'kg' && (int) $items[0]['quantity'] === 2, 'Order did not keep the kilogram quantity');
    check($items[0]['line_total'] === '200.00', 'Line total was not the price times the kilograms');
    $page = http('GET', '/products/' . (new Product())->find($id)['slug']);
    check(str_contains($page, '8 kg') && str_contains($page, '/ kg'), 'Shop did not show the kilogram price and stock');
});

echo $failures === 0 ? "All checks passed\n" : "{$failures} failed\n";
exit($failures === 0 ? 0 : 1);
