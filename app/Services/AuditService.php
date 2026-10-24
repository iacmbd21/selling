<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

class AuditService
{
    public function write(?int $userId, string $action, string $entityType, ?int $entityId, array $metadata = []): void
    {
        try {
            $statement = Database::pdo()->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata_json, ip_address) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $statement->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (\Throwable) {
            // An audit failure must not hide the business result.
        }
    }
}
