<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Services\ReviewService;

class ReviewController extends Controller
{
    public function index(): void
    {
        $this->render('admin/reviews/index', [
            'title' => 'Reviews',
            'reviews' => (new ReviewService())->pending(),
        ], 'layouts/admin');
    }

    public function moderate(string $id): void
    {
        try {
            (new ReviewService())->moderate((int) $id, (string) ($_POST['status'] ?? ''));
            Session::flash('success', 'Review updated.');
        } catch (DomainException $exception) {
            Session::flash('error', $exception->getMessage());
        }
        redirect('/admin/reviews');
    }
}
