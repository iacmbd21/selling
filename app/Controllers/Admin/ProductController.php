<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Money;
use App\Core\Session;
use App\Exceptions\DomainException;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ProductImageService;
use App\Validators\ProductValidator;
use PDOException;

class ProductController extends Controller
{
    public function index(): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $filters = $this->listFilters();
        $result = (new Product())->paginate($filters + ['sort' => 'newest'], $page, Product::PAGE_SIZE, false);
        $this->render('admin/products/index', [
            'title' => 'Products',
            'products' => $result['items'],
            'page' => $result['page'],
            'pages' => $result['pages'],
            'total' => $result['total'],
            'filters' => $filters,
            'categories' => (new Category())->all(),
            'errors' => Session::pull('errors') ?? [],
        ], 'layouts/admin');
    }

    public function edit(string $id): void
    {
        $product = (new Product())->find((int) $id);
        if (!$product) {
            Session::flash('error', 'Product was not found.');
            redirect('/admin/products');
        }
        $this->render('admin/products/edit', [
            'title' => 'Edit product',
            'product' => $product,
            'photos' => $this->photos($product),
            'categories' => (new Category())->all(),
            'errors' => Session::pull('errors') ?? [],
        ], 'layouts/admin');
    }

    public function store(): void
    {
        $errors = (new ProductValidator())->validate($_POST);
        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/products');
        }
        $images = new ProductImageService();
        $stored = [];
        $pdo = Database::pdo();
        try {
            $stored = $images->storeUploads($_FILES['images'] ?? [], ProductImageService::MAX_PHOTOS);
            $pdo->beginTransaction();
            $stock = (int) ($_POST['stock'] ?? 0);
            $name = trim((string) $_POST['name']);
            $id = (new Product())->create([
                'category_id' => (int) $_POST['category_id'],
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'sku' => strtoupper(trim((string) $_POST['sku'])),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'price' => Money::normalize((string) $_POST['price']),
                'stock' => $stock,
                'unit' => (string) ($_POST['unit'] ?? 'pc'),
                'status' => $_POST['status'] ?? 'active',
                'featured' => isset($_POST['featured']) ? 1 : 0,
                'image_path' => $stored[0] ?? null,
            ]);
            $gallery = new ProductImage();
            foreach ($stored as $index => $path) {
                $gallery->add($id, $path, $index);
            }
            (new InventoryMovement())->record(null, $id, 'adjustment', $stock, $stock, 'Opening stock');
            $pdo->commit();
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($stored as $path) {
                $images->delete($path);
            }
            Session::flash('errors', [$exception->getMessage()]);
            redirect('/admin/products');
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($stored as $path) {
                $images->delete($path);
            }
            Session::flash('errors', ['The product could not be saved. Use a unique SKU.']);
            redirect('/admin/products');
        }
        Session::flash('success', 'Product saved.');
        redirect('/admin/products');
    }

    public function update(string $id): void
    {
        $product = (new Product())->find((int) $id);
        if (!$product) {
            Session::flash('error', 'Product was not found.');
            redirect('/admin/products');
        }
        $errors = (new ProductValidator())->validate($_POST + ['stock' => (string) $product['stock']]);
        if ($errors !== []) {
            Session::flash('errors', $errors);
            redirect('/admin/products/' . (int) $id . '/edit');
        }
        $images = new ProductImageService();
        $gallery = new ProductImage();
        $stored = [];
        $removedPaths = [];
        $pdo = Database::pdo();
        try {
            $existing = $this->photos($product);
            $remove = array_map(static fn (mixed $value): int => (int) $value, (array) ($_POST['remove_images'] ?? []));
            $keep = array_values(array_filter(
                $existing,
                static fn (array $photo): bool => !in_array((int) $photo['id'], $remove, true)
            ));
            $stored = $images->storeUploads($_FILES['images'] ?? [], ProductImageService::MAX_PHOTOS - count($keep));
            $ordered = array_merge(array_column($keep, 'path'), $stored);
            $pdo->beginTransaction();
            $name = trim((string) $_POST['name']);
            (new Product())->update((int) $id, [
                'category_id' => (int) $_POST['category_id'],
                'name' => $name,
                'slug' => $this->uniqueSlug($name, (int) $id),
                'sku' => strtoupper(trim((string) $_POST['sku'])),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'price' => Money::normalize((string) $_POST['price']),
                'unit' => (string) ($_POST['unit'] ?? 'pc'),
                'status' => $_POST['status'] ?? 'active',
                'featured' => isset($_POST['featured']) ? 1 : 0,
                'image_path' => $ordered[0] ?? null,
            ]);
            $removedPaths = $gallery->deleteIds((int) $id, $remove);
            $sort = 0;
            foreach ($keep as $photo) {
                $gallery->setSort((int) $photo['id'], $sort);
                $sort++;
            }
            foreach ($stored as $path) {
                $gallery->add((int) $id, $path, $sort);
                $sort++;
            }
            $pdo->commit();
            foreach ($removedPaths as $path) {
                if (!in_array($path, $ordered, true)) {
                    $images->delete($path);
                }
            }
        } catch (DomainException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($stored as $path) {
                $images->delete($path);
            }
            Session::flash('error', $exception->getMessage());
            redirect('/admin/products/' . (int) $id . '/edit');
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            foreach ($stored as $path) {
                $images->delete($path);
            }
            Session::flash('error', 'The product could not be saved. Use a unique SKU.');
            redirect('/admin/products/' . (int) $id . '/edit');
        }
        Session::flash('success', 'Product updated. Past invoices keep the original price.');
        redirect('/admin/products');
    }

    public function destroy(string $id): void
    {
        $product = (new Product())->find((int) $id);
        if (!$product) {
            Session::flash('error', 'Product was not found.');
            redirect('/admin/products');
        }
        $pdo = Database::pdo();
        $orders = $pdo->prepare('SELECT COUNT(*) FROM order_items WHERE product_id = ?');
        $orders->execute([(int) $id]);
        if ((int) $orders->fetchColumn() > 0) {
            Session::flash('error', 'This product is on an order, so it cannot be deleted.');
            redirect('/admin/products');
        }
        $photos = (new ProductImage())->forProduct((int) $id);
        $paths = [];
        foreach (array_merge(array_column($photos, 'path'), [$product['image_path'] ?? null]) as $path) {
            if (is_string($path) && $path !== '' && !in_array($path, $paths, true)) {
                $paths[] = $path;
            }
        }
        try {
            $pdo->beginTransaction();
            $pdo->prepare('DELETE FROM cart_items WHERE product_id = ?')->execute([(int) $id]);
            $pdo->prepare('DELETE FROM product_reviews WHERE product_id = ?')->execute([(int) $id]);
            $pdo->prepare('DELETE FROM inventory_movements WHERE product_id = ?')->execute([(int) $id]);
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([(int) $id]);
            $pdo->commit();
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            Session::flash('error', 'The product could not be deleted.');
            redirect('/admin/products');
        }
        $images = new ProductImageService();
        foreach ($paths as $path) {
            $images->delete($path);
        }
        Session::flash('success', 'Product deleted.');
        redirect('/admin/products');
    }

    private function listFilters(): array
    {
        $clip = static function (string $key): string {
            $value = trim((string) ($_GET[$key] ?? ''));
            return function_exists('mb_substr') ? mb_substr($value, 0, 80) : substr($value, 0, 80);
        };
        $status = (string) ($_GET['status'] ?? '');
        $photo = (string) ($_GET['photo'] ?? '');
        return [
            'name' => $clip('name'),
            'sku' => $clip('sku'),
            'price' => $clip('price'),
            'stock' => $clip('stock'),
            'status' => in_array($status, ['active', 'inactive'], true) ? $status : '',
            'photo' => in_array($photo, ['yes', 'no'], true) ? $photo : '',
        ];
    }

    private function photos(array $product): array
    {
        $gallery = new ProductImage();
        $rows = $gallery->forProduct((int) $product['id']);
        $cover = $product['image_path'] ?? null;
        if ($rows === [] && is_string($cover) && $cover !== '') {
            $gallery->add((int) $product['id'], $cover, 0);
            $rows = $gallery->forProduct((int) $product['id']);
        }
        return $rows;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = slugify($name);
        $slug = $base;
        $suffix = 2;
        $statement = Database::pdo()->prepare('SELECT id FROM products WHERE slug = ?');
        while (true) {
            $statement->execute([$slug]);
            $row = $statement->fetch();
            if (!$row || ($ignoreId !== null && (int) $row['id'] === $ignoreId)) {
                return $slug;
            }
            $slug = $base . '-' . $suffix;
            $suffix++;
        }
    }
}
