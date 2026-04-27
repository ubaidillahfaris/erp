<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Sale extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'invoice_number',
        'date',
        'total_amount',
        'received_amount',
        'change_amount',
        'payment_method',
        'notes',
        'status',
        'cogs_amount',
        'storno_at',
        'storno_reason',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_amount' => 'decimal:2',
            'received_amount' => 'decimal:2',
            'change_amount' => 'decimal:2',
            'cogs_amount' => 'integer',
            'storno_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function journals(): MorphMany
    {
        return $this->morphMany(Journal::class, 'reference');
    }

    public function saleCustomer(): HasOne
    {
        return $this->hasOne(SaleCustomer::class);
    }

    public function payable(): HasOne
    {
        return $this->hasOne(Payable::class, 'reference_id')
            ->where('reference_type', 'sale');
    }
}
