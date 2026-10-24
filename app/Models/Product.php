<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Product
{
    public const PAGE_SIZE = 5;

    public function search(array $filters, bool $onlyActive = true): array
    {
        [$where, $params, $sort] = $this->constraints($filters, $onlyActive);
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE 1 = 1' . $where . ' ORDER BY ' . $sort;
        $statement = Database::pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function paginate(array $filters, int $page, int $perPage, bool $onlyActive = true): array
    {
        $perPage = max(1, $perPage);
        [$where, $params, $sort] = $this->constraints($filters, $onlyActive);
        $count = Database::pdo()->prepare(
            'SELECT COUNT(*) FROM products p JOIN categories c ON c.id = p.category_id WHERE 1 = 1' . $where
        );
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.*, c.name AS category_name, c.slug AS category_slug,
                       (SELECT COUNT(*) FROM product_images i WHERE i.product_id = p.id) AS image_count
                FROM products p
                JOIN categories c ON c.id = p.category_id
                WHERE 1 = 1' . $where . ' ORDER BY ' . $sort . ' LIMIT ' . $perPage . ' OFFSET ' . $offset;
        $statement = Database::pdo()->prepare($sql);
        $statement->execute($params);
        return [
            'items' => $statement->fetchAll(),
            'page' => $page,
            'pages' => $total === 0 ? 0 : $pages,
            'total' => $total,
            'per_page' => $perPage,
        ];
    }

    public function setImagePath(int $id, ?string $path): void
    {
        $statement = Database::pdo()->prepare('UPDATE products SET image_path = ? WHERE id = ?');
        $statement->execute([$path !== null && $path !== '' ? $path : null, $id]);
    }

    public function find(int $id): ?array
    {
        $statement = Database::pdo()->prepare(
            'SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON c.id = p.category_id WHERE p.id = ?'
        );
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findActiveBySlug(string $slug): ?array
    {
        $statement = Database::pdo()->prepare(
            "SELECT p.*, c.name AS category_name
             FROM products p
             JOIN categories c ON c.id = p.category_id
             WHERE p.slug = ? AND p.status = 'active' AND c.status = 'active'"
        );
        $statement->execute([$slug]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function lock(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM products WHERE id = ? FOR UPDATE');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO products (category_id, name, slug, sku, description, price, stock, unit, status, featured, image_path)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['sku'],
            $data['description'],
            $data['price'],
            $data['stock'],
            unit_code($data['unit'] ?? 'pc'),
            $data['status'],
            !empty($data['featured']) ? 1 : 0,
            ($data['image_path'] ?? '') !== '' ? $data['image_path'] : null,
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $statement = Database::pdo()->prepare(
            'UPDATE products SET category_id = ?, name = ?, slug = ?, sku = ?, description = ?, price = ?, unit = ?, status = ?, featured = ?, image_path = ? WHERE id = ?'
        );
        $statement->execute([
            $data['category_id'],
            $data['name'],
            $data['slug'],
            $data['sku'],
            $data['description'],
            $data['price'],
            unit_code($data['unit'] ?? 'pc'),
            $data['status'],
            !empty($data['featured']) ? 1 : 0,
            ($data['image_path'] ?? '') !== '' ? $data['image_path'] : null,
            $id,
        ]);
    }

    private function constraints(array $filters, bool $onlyActive): array
    {
        $sql = '';
        $params = [];
        if ($onlyActive) {
            $sql .= " AND p.status = 'active' AND c.status = 'active'";
        }
        if (($filters['q'] ?? '') !== '') {
            $sql .= " AND (p.name LIKE ? ESCAPE '!' OR p.sku LIKE ? ESCAPE '!' OR p.description LIKE ? ESCAPE '!')";
            $like = '%' . $this->escapeLike((string) $filters['q']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['category_id'])) {
            $sql .= ' AND p.category_id = ?';
            $params[] = (int) $filters['category_id'];
        }
        if (($filters['min_price'] ?? '') !== '') {
            $sql .= ' AND p.price >= ?';
            $params[] = $filters['min_price'];
        }
        if (($filters['max_price'] ?? '') !== '') {
            $sql .= ' AND p.price <= ?';
            $params[] = $filters['max_price'];
        }
        if (!empty($filters['in_stock'])) {
            $sql .= ' AND p.stock > 0';
        }
        foreach (['name' => 'p.name', 'sku' => 'p.sku'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $sql .= " AND {$column} LIKE ? ESCAPE '!'";
                $params[] = '%' . $this->escapeLike((string) $filters[$key]) . '%';
            }
        }
        if (($filters['price'] ?? '') !== '') {
            $sql .= " AND CAST(p.price AS CHAR) LIKE ? ESCAPE '!'";
            $params[] = '%' . $this->escapeLike((string) $filters['price']) . '%';
        }
        if (($filters['stock'] ?? '') !== '') {
            $sql .= " AND CAST(p.stock AS CHAR) LIKE ? ESCAPE '!'";
            $params[] = '%' . $this->escapeLike((string) $filters['stock']) . '%';
        }
        if (in_array($filters['status'] ?? '', ['active', 'inactive'], true)) {
            $sql .= ' AND p.status = ?';
            $params[] = $filters['status'];
        }
        if (($filters['photo'] ?? '') === 'yes') {
            $sql .= " AND p.image_path IS NOT NULL AND p.image_path <> ''";
        } elseif (($filters['photo'] ?? '') === 'no') {
            $sql .= " AND (p.image_path IS NULL OR p.image_path = '')";
        }
        $sorts = [
            'price_asc' => 'p.price ASC, p.name ASC',
            'price_desc' => 'p.price DESC, p.name ASC',
            'newest' => 'p.id DESC',
            'name' => 'p.name ASC',
        ];
        $sort = $sorts[$filters['sort'] ?? 'name'] ?? $sorts['name'];
        return [$sql, $params, $sort];
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value);
    }
}
