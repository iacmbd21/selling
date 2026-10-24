<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Category
{
    public function allActive(): array
    {
        return Database::pdo()->query(
            "SELECT * FROM categories WHERE status = 'active' ORDER BY name"
        )->fetchAll();
    }

    public function all(): array
    {
        return Database::pdo()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM categories WHERE slug = ?');
        $statement->execute([$slug]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(string $name, string $slug): int
    {
        $statement = Database::pdo()->prepare('INSERT INTO categories (name, slug, status) VALUES (?, ?, ?)');
        $statement->execute([$name, $slug, 'active']);
        return (int) Database::pdo()->lastInsertId();
    }
}
