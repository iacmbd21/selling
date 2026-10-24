<?php

declare(strict_types=1);

namespace App\Core;

class RedirectException extends \RuntimeException
{
    public function __construct(public readonly string $location)
    {
        parent::__construct('Redirect');
    }
}
