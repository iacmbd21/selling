<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Models\Product;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    public function store(string $slug): void
    {
        $product = (new Product())->findActiveBySlug($slug);
        if (!$product || !Auth::id()) {
            Session::flash('error', 'Sign in to review a delivered purchase.');
            redirect('/login');
        }
        try {
            (new ReviewService())->submit((int) Auth::id(), (int) $product['id'], (int) ($_POST['rating'] ?? 0), (string) ($_POST['comment'] ?? ''));
            Session::flash('success', 'Review submitted for approval.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('/products/' . $slug);
    }
}
