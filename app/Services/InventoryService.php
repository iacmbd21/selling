<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Exceptions\DomainException;
use App\Models\InventoryMovement;

class InventoryService
{
    public function __construct(private InventoryMovement $movements = new InventoryMovement())
    {
    }

    public function apply(int $productId, int $delta, string $type, ?int $orderId, string $note): int
    {
        if ($delta === 0) {
            $product = (new \App\Models\Product())->lock($productId);
            if (!$product) {
                throw new DomainException('Product was not found.');
            }
            return (int) $product['stock'];
        }

        $pdo = Database::pdo();
        $product = (new \App\Models\Product())->lock($productId);
        if (!$product) {
            throw new DomainException('Product was not found.');
        }
        $newStock = (int) $product['stock'] + $delta;
        if ($newStock < 0) {
            throw new DomainException('Insufficient stock for ' . $product['name'] . '.');
        }
        $update = $pdo->prepare('UPDATE products SET stock = ? WHERE id = ? AND stock = ?');
        $update->execute([$newStock, $productId, (int) $product['stock']]);
        if ($update->rowCount() !== 1) {
            throw new DomainException('Stock changed during checkout. Please try again.');
        }
        $this->movements->record($orderId, $productId, $type, $delta, $newStock, $note);
        return $newStock;
    }

    public function alreadyRestored(int $orderId, string $type): bool
    {
        return $this->movements->hasType($orderId, $type);
    }

    public function reconcile(): array
    {
        $pdo = Database::pdo();
        $rows = $pdo->query(
            'SELECT p.id, p.name, p.sku, p.stock, p.unit,
                    (SELECT m.stock_after FROM inventory_movements m WHERE m.product_id = p.id ORDER BY m.id DESC LIMIT 1) AS movement_stock,
                    (SELECT COALESCE(SUM(m.quantity), 0) FROM inventory_movements m WHERE m.product_id = p.id) AS movement_sum,
                    (SELECT COALESCE(SUM(-m.quantity), 0) FROM inventory_movements m WHERE m.product_id = p.id AND m.type = \'sale\') AS sold_units
             FROM products p
             ORDER BY p.name'
        )->fetchAll();

        $issues = [];
        foreach ($rows as $row) {
            if ($row['movement_stock'] === null || (int) $row['stock'] !== (int) $row['movement_stock']) {
                $issues[] = $row['name'] . ' stock does not match the latest inventory movement.';
            }
            if ((int) $row['stock'] !== (int) $row['movement_sum']) {
                $issues[] = $row['name'] . ' stock does not match the sum of inventory movements.';
            }
        }

        return ['rows' => $rows, 'issues' => $issues];
    }
}
