<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;

class ShippingController extends Controller
{
    public function index(): void
    {
        $zones = Database::pdo()->query(
            'SELECT z.name, z.country_code, z.region_code, z.payment_methods, r.base_fee, r.free_shipping_threshold
             FROM shipping_zones z JOIN shipping_rates r ON r.zone_id = z.id ORDER BY z.id'
        )->fetchAll();
        $this->render('admin/shipping/index', ['title' => 'Shipping', 'zones' => $zones], 'layouts/admin');
    }
}
