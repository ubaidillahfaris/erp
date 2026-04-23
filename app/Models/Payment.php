<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'payable_id',
        'amount',
        'payment_date',
        'payment_method',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Payment $payment) {
            $payment->updatePayableStatus();
        });
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function updatePayableStatus(): void
    {
        $payable = $this->payable;
        $totalPaid = $payable->payments()->sum('amount');

        if ($totalPaid >= $payable->total_amount) {
            $newStatus = 'paid';
        } elseif ($totalPaid > 0) {
            $newStatus = 'partial';
        } else {
            $newStatus = 'open';
        }

        $payable->update([
            'status' => $newStatus,
            'paid_amount' => $totalPaid,
            'remaining_amount' => max(0, $payable->total_amount - $totalPaid),
        ]);

        // Sync payment status back to Restock if applicable
        if ($payable->reference_type === 'restock' && $payable->reference_id) {
            $restockStatus = match ($newStatus) {
                'paid' => 'lunas',
                'partial' => 'bayar_berkala',
                default => null,
            };

            if ($restockStatus) {
                Restock::withoutEvents(function () use ($payable, $restockStatus) {
                    Restock::where('id', $payable->reference_id)->update([
                        'status_pembayaran' => $restockStatus,
                    ]);
                });
            }
        }
    }
}
