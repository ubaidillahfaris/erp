<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreditNote extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'credit_note_number',
        'sale_id',
        'status',
        'reason',
        'total_amount',
        'posted_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
