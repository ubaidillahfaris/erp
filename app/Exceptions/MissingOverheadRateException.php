<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class MissingOverheadRateException extends Exception
{
    public function __construct(string $productName)
    {
        parent::__construct("Gagal menyelesaikan produksi: Produk '{$productName}' belum memiliki data Biaya Overhead per Unit. Silakan edit produk terkait terlebih dahulu.");
    }
}
