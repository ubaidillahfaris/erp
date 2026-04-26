<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PeriodLock extends Model
{
    protected $fillable = [
        'period_month',
        'period_year',
        'is_locked',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    /**
     * Check if a given date is within a locked period.
     */
    public static function isLocked(\DateTimeInterface|string $date): bool
    {
        $dt = Carbon::parse($date);

        return self::where('period_month', $dt->month)
            ->where('period_year', $dt->year)
            ->where('is_locked', true)
            ->exists();
    }
}
