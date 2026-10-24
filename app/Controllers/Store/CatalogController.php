<?php

declare(strict_types=1);

namespace App\Controllers\Store;

use App\Core\Controller;
use App\Core\Response;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;

class CatalogController extends Controller
{
    public function home(): void
    {
        $this->render('store/home', [
            'title' => 'Today Sell',
            'featured' => (new Product())->search([]),
        ]);
    }

    public function category(string $slug): void
    {
        $category = (new Category())->findBySlug($slug);
        if (!$category || $category['status'] !== 'active') {
            Response::abort(404, 'That category does not exist.');
        }
        $_GET['category_id'] = $category['id'];
        $this->index();
    }

    public function index(): void
    {
        $filters = [
            'q' => trim((string) ($_GET['q'] ?? '')),
            'category_id' => ctype_digit((string) ($_GET['category_id'] ?? '')) ? (string) $_GET['category_id'] : '',
            'min_price' => is_numeric($_GET['min_price'] ?? null) ? (string) $_GET['min_price'] : '',
            'max_price' => is_numeric($_GET['max_price'] ?? null) ? (string) $_GET['max_price'] : '',
            'sort' => (string) ($_GET['sort'] ?? 'name'),
            'in_stock' => isset($_GET['in_stock']) ? '1' : '',
        ];
        $this->render('store/catalog', [
            'title' => 'Products',
            'products' => (new Product())->search($filters),
            'categories' => (new Category())->allActive(),
            'filters' => $filters,
        ]);
    }

    public function show(string $slug): void
    {
        $product = (new Product())->findActiveBySlug($slug);
        if (!$product) {
            Response::abort(404, 'That product is not available.');
        }
        $gallery = (new ProductImage())->forProduct((int) $product['id']);
        if ($gallery === [] && is_string($product['image_path'] ?? null) && $product['image_path'] !== '') {
            $gallery = [['path' => $product['image_path']]];
        }
        $this->render('store/product', [
            'title' => $product['name'],
            'product' => $product,
            'gallery' => $gallery,
            'reviews' => (new \App\Services\ReviewService())->published((int) $product['id']),
        ]);
    }
}
