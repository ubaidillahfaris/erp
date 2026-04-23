<?php

namespace App\Exceptions;

use Exception;

class MissingCOGSException extends Exception
{
    public function __construct(string $message = 'HPP (COGS) penjualan tidak valid atau kosong. Penjualan tidak dapat diselesaikan.')
    {
        parent::__construct($message);
    }
}
