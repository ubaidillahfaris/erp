<?php

namespace App\Exceptions;

use Exception;

class InvalidPurchaseAmountException extends Exception
{
    public function __construct(string $message = 'Total biaya pembelian tidak valid atau nol.')
    {
        parent::__construct($message);
    }
}
