<?php

declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;

$fresh = in_array('--fresh', $argv ?? [], true);
$name = env('DB_NAME', 'today_sell');
if (!preg_match('/^[A-Za-z0-9_]+$/', (string) $name)) {
    fwrite(STDERR, "Invalid database name\n");
    exit(1);
}

$server = Database::server();
if ($fresh) {
    $server->exec('DROP DATABASE IF EXISTS `' . $name . '`');
}
$server->exec('CREATE DATABASE IF NOT EXISTS `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
Database::disconnect();

$pdo = Database::pdo();
foreach (glob(__DIR__ . '/migrations/*.sql') ?: [] as $file) {
    $sql = file_get_contents($file);
    foreach (preg_split('/;\s*\R/', (string) $sql) ?: [] as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

echo "Migrated {$name}\n";
