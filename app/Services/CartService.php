<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Models\Cart as CartModel;
use App\Models\Product;

class CartService
{
    public function __construct(private CartModel $carts = new CartModel(), private Product $products = new Product())
    {
    }

    public function items(): array
    {
        $cart = $this->current(false);
        if (!$cart) {
            return [];
        }
        return $this->carts->items((int) $cart['id']);
    }

    public function count(): int
    {
        $count = 0;
        foreach ($this->items() as $item) {
            $count += (int) $item['quantity'];
        }
        return $count;
    }

    public function add(int $productId, int $quantity): void
    {
        $product = $this->products->find($productId);
        if (!$product || $product['status'] !== 'active') {
            throw new \App\Exceptions\DomainException('That product is not available.');
        }
        if ($quantity < 1 || $quantity > 20) {
            throw new \App\Exceptions\DomainException('Quantity must be between 1 and 20.');
        }
        if ((int) $product['stock'] < $quantity) {
            throw new \App\Exceptions\DomainException('Insufficient stock for ' . $product['name'] . '.');
        }
        $cart = $this->current(true);
        $this->carts->addItem((int) $cart['id'], $productId, $quantity);
    }

    public function update(int $productId, int $quantity): void
    {
        $cart = $this->current(false);
        if ($cart) {
            $this->carts->setQuantity((int) $cart['id'], $productId, $quantity);
        }
    }

    public function remove(int $productId): void
    {
        $cart = $this->current(false);
        if ($cart) {
            $this->carts->removeItem((int) $cart['id'], $productId);
        }
    }

    public function clear(): void
    {
        $cart = $this->current(false);
        if ($cart) {
            $this->carts->clear((int) $cart['id']);
        }
    }

    private function current(bool $create): ?array
    {
        $sessionId = session_id() ?: 'guest';
        $cart = $this->carts->findFor(Auth::id(), $sessionId);
        if (!$cart && $create) {
            $id = $this->carts->create(Auth::id(), $sessionId);
            $cart = ['id' => $id];
        }
        return $cart;
    }
}
