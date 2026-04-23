<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class BalanceMismatchException extends Exception
{
    public function __construct(int $difference = 0)
    {
        $message = 'Journal entry is not balanced. Difference: '.($difference / 100).' (in base currency units).';
        parent::__construct($message);
    }
}
