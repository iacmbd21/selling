<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public function find(int $id): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM users WHERE id = ?');
        $statement->execute([$id]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $statement = Database::pdo()->prepare('SELECT * FROM users WHERE email = ?');
        $statement->execute([strtolower(trim($email))]);
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $statement = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password, mobile, role, status) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([
            $data['name'],
            strtolower(trim($data['email'])),
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['mobile'] ?? null,
            $data['role'] ?? 'customer',
            $data['status'] ?? 'active',
        ]);
        return (int) Database::pdo()->lastInsertId();
    }

    public function updatePassword(int $id, string $password): void
    {
        $statement = Database::pdo()->prepare('UPDATE users SET password = ? WHERE id = ?');
        $statement->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }

    public function allStaff(): array
    {
        return Database::pdo()->query(
            "SELECT id, name, email, mobile, role, status, created_at FROM users WHERE role IN ('admin','manager','staff') ORDER BY role, name"
        )->fetchAll();
    }
}
