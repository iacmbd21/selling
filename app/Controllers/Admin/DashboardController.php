<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class DashboardController extends Controller
{
    public function index(): void
    {
        $pdo = Database::pdo();
        $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'ordersToday' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'unpaidCod' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_method = 'cod' AND payment_status = 'pending' AND status NOT IN ('cancelled','returned')")->fetchColumn(),
            'pendingPayments' => (int) $pdo->query("SELECT COUNT(*) FROM payments WHERE status = 'submitted'")->fetchColumn(),
            'trend' => $pdo->query("SELECT DATE(created_at) AS day, COALESCE(SUM(total),0) AS total FROM orders WHERE status IN ('pending','confirmed','processing','shipped','delivered') AND created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY) GROUP BY DATE(created_at) ORDER BY day")->fetchAll(),
            'lowStock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active' AND stock <= 5")->fetchColumn(),
            'recent' => $pdo->query('SELECT * FROM orders ORDER BY id DESC LIMIT 8')->fetchAll(),
        ], 'layouts/admin');
    }
}
