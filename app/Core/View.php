<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/store'): string
    {
        $viewFile = BASE_PATH . '/app/Views/' . $template . '.php';
        if (!is_file($viewFile)) {
            throw new \RuntimeException('View not found: ' . $template);
        }

        $content = (static function () use ($viewFile, $data): string {
            extract($data, EXTR_SKIP);
            ob_start();
            require $viewFile;
            return (string) ob_get_clean();
        })();

        if ($layout === null) {
            return $content;
        }

        $layoutFile = BASE_PATH . '/app/Views/' . $layout . '.php';
        extract($data, EXTR_SKIP);
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }
}
