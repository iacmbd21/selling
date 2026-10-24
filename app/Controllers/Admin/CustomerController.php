<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class CustomerController extends Controller
{
    public function index(): void
    {
        $customers = Database::pdo()->query(
            "SELECT id, name, email, mobile, status, created_at FROM users WHERE role = 'customer' ORDER BY id DESC"
        )->fetchAll();
        $this->render('admin/customers/index', ['title' => 'Customers', 'customers' => $customers], 'layouts/admin');
    }
}
