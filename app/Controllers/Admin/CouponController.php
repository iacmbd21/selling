<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Money;
use App\Core\Session;
use App\Models\Coupon;
use App\Validators\CouponValidator;

class CouponController extends Controller
{
    public function index(): void
    {
        $this->render('admin/coupons/index', [
            'title' => 'Coupons',
            'coupons' => (new Coupon())->all(),
            'errors' => Session::pull('errors') ?? [],
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $errors = (new CouponValidator())->validate($_POST);
        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/coupons');
        }
        $max = trim((string) ($_POST['max_uses'] ?? ''));
        (new Coupon())->create([
            'code' => $_POST['code'],
            'type' => $_POST['type'],
            'value' => Money::normalize((string) $_POST['value']),
            'min_order' => Money::normalize((string) ($_POST['min_order'] ?? '0')),
            'max_uses' => $max === '' ? null : (int) $max,
            'starts_at' => ($_POST['starts_at'] ?? '') !== '' ? str_replace('T', ' ', $_POST['starts_at']) . ':00' : null,
            'expires_at' => ($_POST['expires_at'] ?? '') !== '' ? str_replace('T', ' ', $_POST['expires_at']) . ':00' : null,
            'status' => 'active',
        ]);
        Session::flash('success', 'Coupon saved.');
        redirect('/admin/coupons');
    }
}
