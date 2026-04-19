<?php

declare(strict_types=1);

namespace App\DTOs;

class JournalItemData
{
    public function __construct(
        public int $account_id,
        public int $amount, // Stored in cents
        public string $type, // 'debit' or 'credit'
    ) {
    }
}
