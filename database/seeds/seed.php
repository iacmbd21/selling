<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Money;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;

$pdo = Database::pdo();
$existing = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
if ($existing > 0) {
    echo "Database already has users. Skip seeding, or run php database/migrate.php --fresh first.\n";
    exit(0);
}

$categories = new Category();
$products = new Product();
$movements = new InventoryMovement();
$users = new User();
$coupons = new Coupon();

$categoryIds = [];
foreach (['Grocery', 'Electronics', 'Fashion', 'Stationery'] as $name) {
    $categoryIds[$name] = $categories->create($name, slugify($name));
}

$catalog = [
    ['Miniket Rice 5kg', 'Grocery', 'RICE-5KG', '420.00', 20, 'kg', 'Everyday rice for the household.', 'active'],
    ['Soybean Oil 5L', 'Grocery', 'OIL-5L', '980.00', 10, 'l', 'Cooking oil in a 5 litre bottle.', 'active'],
    ['Fresh Milk 1L', 'Grocery', 'MILK-1L', '90.00', 30, 'l', 'Chilled milk, sold while in stock.', 'active'],
    ['Wireless Earbuds', 'Electronics', 'EAR-001', '1250.00', 6, 'pc', 'In-ear wireless earbuds with a charging case.', 'active'],
    ['Cotton Panjabi', 'Fashion', 'PAN-M', '1800.00', 4, 'pc', 'Medium cotton panjabi.', 'active'],
    ['School Notebook', 'Stationery', 'NOTE-80', '60.00', 40, 'pc', '80-page ruled notebook.', 'active'],
    ['Hidden Lamp', 'Electronics', 'LAMP-HIDE', '300.00', 3, 'pc', 'Inactive sample that shoppers should not see.', 'inactive'],
];

foreach ($catalog as [$name, $category, $sku, $price, $stock, $unit, $description, $status]) {
    $id = $products->create([
        'category_id' => $categoryIds[$category],
        'name' => $name,
        'slug' => slugify($name),
        'sku' => $sku,
        'description' => $description,
        'price' => Money::normalize($price),
        'stock' => $stock,
        'unit' => $unit,
        'status' => $status,
    ]);
    $movements->record(null, $id, 'adjustment', $stock, $stock, 'Opening stock');
}

$users->create(['name' => 'Admin User', 'email' => 'admin@todaysell.test', 'password' => 'password', 'role' => 'admin']);
$users->create(['name' => 'Shop Manager', 'email' => 'manager@todaysell.test', 'password' => 'password', 'role' => 'manager']);
$users->create(['name' => 'Counter Staff', 'email' => 'staff@todaysell.test', 'password' => 'password', 'role' => 'staff']);
$users->create(['name' => 'Rahim Customer', 'email' => 'customer@todaysell.test', 'password' => 'password', 'mobile' => '01711112222', 'role' => 'customer']);

$coupons->create(['code' => 'SAVE10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => 100, 'starts_at' => null, 'expires_at' => null]);
$coupons->create(['code' => 'FLAT50', 'type' => 'fixed', 'value' => '50.00', 'min_order' => '200.00', 'max_uses' => null, 'starts_at' => null, 'expires_at' => null]);
$coupons->create(['code' => 'EXPIRED10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => null, 'starts_at' => null, 'expires_at' => date('Y-m-d H:i:s', strtotime('-1 day'))]);
$coupons->create(['code' => 'MAXED5', 'type' => 'percent', 'value' => '5.00', 'min_order' => '0.00', 'max_uses' => 1, 'used_count' => 1, 'starts_at' => null, 'expires_at' => null]);
$coupons->create(['code' => 'SOON10', 'type' => 'percent', 'value' => '10.00', 'min_order' => '0.00', 'max_uses' => null, 'starts_at' => date('Y-m-d H:i:s', strtotime('+2 days')), 'expires_at' => null]);

$pdo->prepare('INSERT INTO app_meta (meta_key, meta_value) VALUES (?, ?)')->execute(['schema', '001']);

echo "Seeded demo shop. Password for every account: password\n";
