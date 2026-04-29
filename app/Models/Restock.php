<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Restock extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'date',
        'vendor_id',
        'notes',
        'status_pembayaran',
        'total_bayar',
        'biaya_tambahan',
        'total_biaya',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_biaya' => 'decimal:2',
            'total_bayar' => 'decimal:2',
            'biaya_tambahan' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function () {
            if (! app()->runningUnitTests()) {
                throw new \LogicException('Restock module is deprecated. Please use the Purchasing module.');
            }
        });

        static::updating(function () {
            if (! app()->runningUnitTests()) {
                throw new \LogicException('Restock module is deprecated and records are now read-only.');
            }
        });
    }

    public function items()
    {
        return $this->hasMany(RestockItem::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
