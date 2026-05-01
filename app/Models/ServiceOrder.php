<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServiceOrder extends Model
{
    use Auditable, BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'order_number',
        'service_id',
        'customer_type',
        'party_type',
        'party_id',
        'order_date',
        'completion_date',
        'current_status_code',
        'notes',
        'total_amount',
        'total_paid',
        'status',
        'created_by',
        'journal_entry_id',
    ];

    protected $casts = [
        'order_date' => 'date',
        'completion_date' => 'datetime',
        'total_amount' => 'integer', // cents
        'total_paid' => 'integer',   // cents
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function party(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(ServiceOrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServiceOrderPayment::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the remaining balance in cents.
     */
    public function getBalanceAttribute(): int
    {
        return max(0, $this->total_amount - $this->total_paid);
    }
}
