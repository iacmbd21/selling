<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Access;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Response;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Exceptions\ValidationException;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Services\CartService;
use App\Services\InvoiceService;
use App\Services\OrderService;

class CheckoutController extends Controller
{
    public function create(): void
    {
        $this->render('store/checkout', $this->state());
    }

    public function store(): void
    {
        $action = (string) ($_POST['action'] ?? 'place');
        try {
            if ($action === 'preview') {
                Session::flash('quote', (new OrderService())->quote($_POST));
                Session::flash('old', $_POST);
                redirect('/order');
            }
            $order = (new OrderService())->place($_POST, Auth::id());
            (new CartService())->clear();
            Session::rememberOrder((string) $order['order_no']);
            unset($_SESSION['checkout_key']);
            Session::flash('success', 'Order placed.');
            redirect('/orders/' . $order['order_no']);
        } catch (ValidationException $exception) {
            Session::flash('errors', $exception->errors);
            Session::flash('old', $_POST);
            redirect('/order');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
            Session::flash('old', $_POST);
            redirect('/order');
        }
    }

    public function show(string $orderNo): void
    {
        $orders = new Order();
        $order = $orders->findByNumber($orderNo);
        if (!$order || !Access::canViewOrder($order)) {
            Response::abort(404, 'Order was not found.');
        }
        $this->render('store/confirmation', [
            'title' => $order['order_no'],
            'order' => $order,
            'items' => $orders->items((int) $order['id']),
            'invoice' => (new InvoiceService())->findByOrder((int) $order['id']),
        ]);
    }

    private function checkoutKey(): string
    {
        if (empty($_SESSION['checkout_key']) || !is_string($_SESSION['checkout_key'])) {
            $_SESSION['checkout_key'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['checkout_key'];
    }

    public function state(): array
    {
        $old = Session::pull('old') ?? [];
        $selected = (int) ($_GET['product'] ?? 0);
        $lines = [];
        if (!empty($old['items']) && is_array($old['items'])) {
            $lines = array_values($old['items']);
        } elseif (!empty($old['product_id'])) {
            $lines[] = ['product_id' => $old['product_id'], 'quantity' => $old['quantity'] ?? 1];
        } else {
            foreach ((new CartService())->items() as $item) {
                if ($selected === 0) {
                    $lines[] = ['product_id' => $item['id'], 'quantity' => $item['quantity']];
                }
            }
        }
        if ($lines === []) {
            $lines[] = ['product_id' => $selected, 'quantity' => 1];
        }

        $user = Auth::user();
        return [
            'title' => 'Place an order',
            'products' => (new Product())->search([]),
            'categories' => (new Category())->allActive(),
            'lines' => $lines,
            'old' => $old,
            'errors' => Session::pull('errors') ?? [],
            'quote' => Session::pull('quote'),
            'prefillName' => $user['name'] ?? '',
            'prefillMobile' => $user['mobile'] ?? '',
            'checkoutKey' => $this->checkoutKey(),
        ];
    }
}
