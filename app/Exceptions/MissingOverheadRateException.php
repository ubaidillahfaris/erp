<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class MissingOverheadRateException extends Exception
{
    public function __construct(string $productName)
    {
        parent::__construct("Cannot complete production: Product '{$productName}' is missing overhead_rate_per_unit.");
    }
}
