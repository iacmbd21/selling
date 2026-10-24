<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Models\Order;
use App\Models\Payment;
use App\Services\InvoiceService;
use App\Services\OrderService;

class OrderController extends Controller
{
    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'status' => (string) ($_GET['status'] ?? ''),
            'payment_status' => (string) ($_GET['payment_status'] ?? ''),
        ];
        $this->render('admin/orders/index', [
            'title' => 'Orders',
            'orders' => (new Order())->search($filters),
            'filters' => $filters,
        ], 'layouts/admin');
    }

    public function show(string $id): void
    {
        $orders = new Order();
        $order = $orders->find((int) $id);
        if (!$order) {
            Response::abort(404, 'Order was not found.');
        }
        $this->render('admin/orders/show', [
            'title' => $order['order_no'],
            'order' => $order,
            'items' => $orders->items((int) $order['id']),
            'payments' => (new Payment())->forOrder((int) $order['id']),
            'invoice' => (new InvoiceService())->findByOrder((int) $order['id']),
        ], 'layouts/admin');
    }

    public function confirm(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->confirm((int) $id), 'Order confirmed.');
    }

    public function process(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->process((int) $id), 'Order is being packed.');
    }

    public function ship(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->ship((int) $id, trim((string) ($_POST['courier_name'] ?? '')), trim((string) ($_POST['tracking_number'] ?? ''))), 'Shipment recorded.');
    }

    public function deliver(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->deliver((int) $id), 'Order marked delivered.');
    }

    public function cancel(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->cancel((int) $id), 'Order cancelled and stock restored.');
    }

    public function returnOrder(string $id): void
    {
        $this->act((int) $id, fn (OrderService $orders) => $orders->returnOrder((int) $id), 'Return recorded and stock restored.');
    }

    private function act(int $id, callable $callback, string $message): void
    {
        try {
            $callback(new OrderService());
            Session::flash('success', $message);
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('/admin/orders/' . $id);
    }
}
