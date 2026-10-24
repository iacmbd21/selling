<?php

declare(strict_types=1);

namespace App\Core;

class Controller
{
    protected function render(string $template, array $data = [], ?string $layout = 'layouts/store'): void
    {
        echo View::render($template, $data, $layout);
    }
}
