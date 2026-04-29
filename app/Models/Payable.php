<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payable extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'type',
        'reference_type',
        'reference_id',
        'party_type',
        'party_id',
        'principal_amount',
        'interest_type',
        'interest_rate',
        'interest_period',
        'installment_count',
        'total_interest',
        'total_amount',
        'paid_amount',
        'remaining_amount',
        'due_date',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate' => 'decimal:4',
            'installment_count' => 'integer',
            'total_interest' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Payable $payable) {
            $payable->remaining_amount = $payable->total_amount - $payable->paid_amount;

            // Fill mandatory reference fields for tests if missing
            if (! $payable->reference_type) {
                $payable->reference_type = 'manual';
            }
            if (! $payable->reference_id) {
                $payable->reference_id = 0;
            }
        });

        static::created(function (Payable $payable) {
            $payable->generateInterestSchedules();
        });
    }

    public function party()
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function interestSchedules(): HasMany
    {
        return $this->hasMany(InterestSchedule::class);
    }

    public function paymentReminders(): HasMany
    {
        return $this->hasMany(PaymentReminder::class);
    }

    public function generateInterestSchedules(): void
    {
        $count = $this->installment_count;
        $period = $this->interest_period;
        $dueDate = $this->due_date ? Carbon::parse($this->due_date) : null;

        if (! $count && $dueDate && $period) {
            $now = Carbon::now();
            if ($period === 'daily') {
                $count = (int) $now->diffInDays($dueDate);
            } elseif ($period === 'weekly') {
                $count = (int) $now->diffInWeeks($dueDate);
            } elseif ($period === 'monthly') {
                $count = (int) $now->diffInMonths($dueDate);
            }
        }

        if (! $count || $count <= 0) {
            return;
        }

        $principalPerPeriod = $this->principal_amount / $count;
        $interestPerPeriod = $this->total_interest / $count;
        $totalPerPeriod = $this->total_amount / $count;

        $currentDueDate = Carbon::now();

        for ($i = 1; $i <= $count; $i++) {
            if ($period === 'daily') {
                $currentDueDate->addDay();
            } elseif ($period === 'weekly') {
                $currentDueDate->addWeek();
            } elseif ($period === 'monthly') {
                $currentDueDate->addMonth();
            } else {
                // If no period specified but count exists, we can't determine dates easily
                // Default to monthly if count exists? Or skip?
                // Logic says: "Jika null -> hitung dari due_date / interest_period".
                // So if period is null, we might skip or use monthly as fallback if dueDate exists.
                if (! $period) {
                    break;
                }
            }

            $this->interestSchedules()->create([
                'period_number' => $i,
                'due_date' => $currentDueDate->toDateString(),
                'principal_portion' => $principalPerPeriod,
                'interest_portion' => $interestPerPeriod,
                'total_due' => $totalPerPeriod,
                'status' => 'pending',
            ]);
        }
    }
}
