<?php

declare(strict_types=1);

namespace App\DTOs;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

class JournalEntryData
{
    /**
     * @param  JournalItemData[]  $items
     */
    public function __construct(
        public array $items,
        public ?DateTimeInterface $tanggal = null,
        public ?string $ref_number = null,
        public ?string $description = null,
        public ?Model $journalable = null,
        public ?int $created_by = null,
    ) {}
}
