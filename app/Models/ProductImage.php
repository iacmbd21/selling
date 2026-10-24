<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ProductImage
{
    public function forProduct(int $productId): array
    {
        $statement = Database::pdo()->prepare(
            'SELECT id, product_id, path, sort_order FROM product_images WHERE product_id = ? ORDER BY sort_order, id'
        );
        $statement->execute([$productId]);
        return $statement->fetchAll();
    }

    public function add(int $productId, string $path, int $sort): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO product_images (product_id, path, sort_order) VALUES (?, ?, ?)'
        );
        $statement->execute([$productId, $path, $sort]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function setSort(int $id, int $sort): void
    {
        $statement = Database::pdo()->prepare('UPDATE product_images SET sort_order = ? WHERE id = ?');
        $statement->execute([$sort, $id]);
    }

    public function deleteIds(int $productId, array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(static fn (mixed $id): int => (int) $id, $ids))));
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $select = Database::pdo()->prepare(
            "SELECT path FROM product_images WHERE product_id = ? AND id IN ($placeholders)"
        );
        $select->execute([$productId, ...$ids]);
        $paths = $select->fetchAll(\PDO::FETCH_COLUMN);
        $delete = Database::pdo()->prepare(
            "DELETE FROM product_images WHERE product_id = ? AND id IN ($placeholders)"
        );
        $delete->execute([$productId, ...$ids]);
        return array_map(static fn (mixed $path): string => (string) $path, $paths);
    }
}
