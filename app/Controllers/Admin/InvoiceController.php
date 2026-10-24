<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Access;
use App\Core\Controller;
use App\Core\Response;
use App\Models\Order;
use App\Services\InvoiceService;

class InvoiceController extends Controller
{
    public function show(string $invoiceNo): void
    {
        $service = new InvoiceService();
        $invoice = $service->findByNumber($invoiceNo);
        if (!$invoice) {
            Response::abort(404, 'Invoice was not found.');
        }
        $order = (new Order())->find((int) $invoice['order_id']);
        if (!$order || !Access::canViewOrder($order)) {
            Response::abort(404, 'Invoice was not found.');
        }
        echo $service->render($invoice);
    }
}
