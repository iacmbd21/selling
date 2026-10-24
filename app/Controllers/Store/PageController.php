<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Models\Order;
use App\Validators\OrderValidator;

class PageController extends Controller
{
    public function trackForm(): void
    {
        $this->render('store/track', ['title' => 'Track an order']);
    }

    public function track(): void
    {
        $mobile = (new OrderValidator())->normalizeMobile((string) ($_POST['mobile'] ?? ''));
        $order = (new Order())->findByNumberAndMobile(trim((string) ($_POST['order_no'] ?? '')), $mobile);
        if (!$order) {
            Session::flash('error', 'Those order details do not match.');
            redirect('/track-order');
        }
        Session::rememberOrder((string) $order['order_no']);
        redirect('/orders/' . $order['order_no']);
    }

    public function contactForm(): void
    {
        $this->render('store/contact', ['title' => 'Contact']);
    }

    public function contact(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        if (str_length($name) < 2 || !filter_var($email, FILTER_VALIDATE_EMAIL) || str_length($message) < 10) {
            Session::flash('error', 'Enter your name, email, and a message.');
            redirect('/contact');
        }
        Database::pdo()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)')->execute([$name, $email, $message]);
        Session::flash('success', 'Message received.');
        redirect('/contact');
    }

    public function page(string $slug): void
    {
        $statement = Database::pdo()->prepare('SELECT * FROM pages WHERE slug = ?');
        $statement->execute([$slug]);
        $page = $statement->fetch();
        if (!$page) {
            \App\Core\Response::abort(404, 'That page does not exist.');
        }
        $this->render('store/page', ['title' => $page['title'], 'page' => $page]);
    }
}
