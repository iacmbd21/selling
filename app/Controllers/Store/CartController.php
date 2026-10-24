<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Services\CartService;

class CartController extends Controller
{
    public function index(): void
    {
        $this->render('store/cart', [
            'title' => 'Cart',
            'items' => (new CartService())->items(),
        ]);
    }

    public function update(): void
    {
        (new CartService())->update((int) ($_POST['product_id'] ?? 0), (int) ($_POST['quantity'] ?? 1));
        redirect('/cart');
    }

    public function remove(): void
    {
        (new CartService())->remove((int) ($_POST['product_id'] ?? 0));
        redirect('/cart');
    }

    public function add(): void
    {
        try {
            (new CartService())->add((int) ($_POST['product_id'] ?? 0), (int) ($_POST['quantity'] ?? 1));
            Session::flash('success', 'Added to cart.');
            redirect('/cart');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
            redirect('/products');
        }
    }
}
