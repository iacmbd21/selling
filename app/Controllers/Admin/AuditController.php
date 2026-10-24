<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class AuditController extends Controller
{
    public function index(): void
    {
        $logs = Database::pdo()->query('SELECT * FROM audit_logs ORDER BY id DESC LIMIT 100')->fetchAll();
        $this->render('admin/audit/index', ['title' => 'Audit log', 'logs' => $logs], 'layouts/admin');
    }
}
