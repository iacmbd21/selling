<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\DomainException;

class ProductImageService
{
    public const MAX_PHOTOS = 6;

    private const MAX_BYTES = 2097152;

    public function storeUploads(array $files, int $max): array
    {
        $pending = $this->pendingUploads($files);
        if ($max < 0) {
            $max = 0;
        }
        if (count($pending) > $max) {
            throw new DomainException('A product can have up to ' . self::MAX_PHOTOS . ' photos.');
        }
        $stored = [];
        try {
            foreach ($pending as $file) {
                $stored[] = $this->store($file);
            }
        } catch (DomainException $exception) {
            foreach ($stored as $path) {
                $this->delete($path);
            }
            throw $exception;
        }
        return $stored;
    }

    public function store(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new DomainException('Choose an image to upload.');
        }
        if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
            throw new DomainException('Use an image smaller than 2 MB.');
        }
        $temporary = (string) ($file['tmp_name'] ?? '');
        if ($error !== UPLOAD_ERR_OK || $temporary === '' || !is_uploaded_file($temporary)) {
            throw new DomainException('The image could not be uploaded.');
        }
        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            throw new DomainException('Use an image smaller than 2 MB.');
        }
        $bytes = file_get_contents($temporary);
        if ($bytes === false) {
            throw new DomainException('The image could not be uploaded.');
        }
        return $this->storeBytes($bytes);
    }

    public function storeBytes(string $bytes): string
    {
        if ($bytes === '' || strlen($bytes) > self::MAX_BYTES) {
            throw new DomainException('Use an image smaller than 2 MB.');
        }
        $info = @getimagesizefromstring($bytes);
        $mime = is_array($info) ? (string) ($info['mime'] ?? '') : '';
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!isset($extensions[$mime])) {
            throw new DomainException('Upload a JPG, PNG, WEBP, or GIF image.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if ($finfo->buffer($bytes) !== $mime) {
            throw new DomainException('Upload a JPG, PNG, WEBP, or GIF image.');
        }

        $directory = BASE_PATH . '/public/uploads/products';
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new DomainException('The image could not be saved.');
        }
        $relative = 'uploads/products/' . bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
        if (file_put_contents(BASE_PATH . '/public/' . $relative, $bytes) === false) {
            throw new DomainException('The image could not be saved.');
        }
        return $relative;
    }

    private function pendingUploads(array $files): array
    {
        if (!isset($files['error'])) {
            return [];
        }
        if (!is_array($files['error'])) {
            return (int) $files['error'] === UPLOAD_ERR_NO_FILE ? [] : [$files];
        }
        $pending = [];
        foreach ($files['error'] as $index => $error) {
            if ((int) $error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $pending[] = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $error,
                'size' => $files['size'][$index] ?? 0,
            ];
        }
        return $pending;
    }

    public function delete(?string $path): void
    {
        if ($path === null || !preg_match('#^uploads/products/[a-f0-9]{32}\.(jpg|png|webp|gif)$#', $path)) {
            return;
        }
        $full = BASE_PATH . '/public/' . $path;
        if (is_file($full)) {
            unlink($full);
        }
    }
}
