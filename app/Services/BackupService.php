<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;

class BackupService
{
    public const TABLES = [
        'users',
        'categories',
        'products',
        'product_images',
        'coupons',
        'carts',
        'cart_items',
        'orders',
        'order_items',
        'payments',
        'inventory_movements',
        'invoices',
        'app_meta',
    ];

    public function create(): string
    {
        $directory = BASE_PATH . '/storage/backups';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
        $path = $directory . '/today-sell-' . date('Ymd-His') . '.sql';
        file_put_contents($path, $this->dump());
        return $path;
    }

    public function dump(): string
    {
        $pdo = Database::pdo();
        $sql = "SET FOREIGN_KEY_CHECKS=0;\n";
        foreach (array_reverse(self::TABLES) as $table) {
            $sql .= 'DELETE FROM `' . $table . "`;\n";
        }
        foreach (self::TABLES as $table) {
            $rows = $pdo->query('SELECT * FROM `' . $table . '`')->fetchAll();
            foreach ($rows as $row) {
                $columns = array_map(static fn (string $column): string => '`' . $column . '`', array_keys($row));
                $values = array_map(static function (mixed $value) use ($pdo): string {
                    if ($value === null) {
                        return 'NULL';
                    }
                    return $pdo->quote((string) $value);
                }, array_values($row));
                $sql .= 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ");\n";
            }
            $next = (int) $pdo->query('SELECT IFNULL(MAX(id), 0) + 1 FROM `' . $table . '`')->fetchColumn();
            $sql .= 'ALTER TABLE `' . $table . '` AUTO_INCREMENT = ' . $next . ";\n";
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    public function restore(string $sql): void
    {
        $statements = preg_split('/;\s*(?:\r?\n|$)/', trim($sql)) ?: [];
        $pdo = Database::pdo();
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            if (!preg_match('/^(SET|DELETE|INSERT|ALTER)\b/i', $statement)) {
                throw new DomainException('The backup contains a statement that cannot be restored.');
            }
            if (!preg_match('/\b(users|categories|products|product_images|coupons|carts|cart_items|orders|order_items|payments|inventory_movements|invoices|app_meta|FOREIGN_KEY_CHECKS)\b/i', $statement)) {
                throw new DomainException('The backup contains an unexpected statement.');
            }
            $pdo->exec($statement);
        }
    }

    public function files(): array
    {
        $directory = BASE_PATH . '/storage/backups';
        if (!is_dir($directory)) {
            return [];
        }
        $files = glob($directory . '/*.sql') ?: [];
        rsort($files);
        return array_map(static fn (string $file): string => basename($file), $files);
    }

    public function read(string $filename): string
    {
        $filename = basename($filename);
        if (!preg_match('/^today-sell-\d{8}-\d{6}\.sql$/', $filename)) {
            throw new DomainException('Choose a backup file created by this shop.');
        }
        $path = BASE_PATH . '/storage/backups/' . $filename;
        if (!is_file($path)) {
            throw new DomainException('Backup file was not found.');
        }
        $sql = file_get_contents($path);
        if ($sql === false) {
            throw new DomainException('Backup file could not be read.');
        }
        return $sql;
    }
}
