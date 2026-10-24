<?php

declare(strict_types=1);

namespace App\Controllers\Customer;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\Order;

class AccountController extends Controller
{
    public function orders(): void
    {
        $this->render('customer/orders', [
            'title' => 'My orders',
            'orders' => (new Order())->forUser((int) Auth::id()),
        ]);
    }
}
