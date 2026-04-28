<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepreciationSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'fixed_asset_id',
        'period_month',
        'period_year',
        'depreciation_amount',
        'book_value_after',
        'journal_entry_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'depreciation_amount' => 'integer',
            'book_value_after' => 'integer',
        ];
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }
}
