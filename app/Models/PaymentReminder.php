<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentReminder extends Model
{
    protected $fillable = [
        'payable_id',
        'reminder_date',
        'remind_before_days',
        'channel',
        'status',
        'sent_at',
        'message',
    ];

    protected function casts(): array
    {
        return [
            'reminder_date' => 'date',
            'remind_before_days' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(Payable::class);
    }
}
