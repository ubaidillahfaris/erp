<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use Auditable, HasFactory;

    protected static function booted(): void
    {
        static::updating(function ($journalEntry) {
            throw new \RuntimeException('Journal entries are immutable. Use reversing journal (storno) to make changes.');
        });

        static::deleting(function ($journalEntry) {
            throw new \RuntimeException('Journal entries are immutable and cannot be deleted.');
        });
    }

    protected $fillable = [
        'ref_number',
        'date',
        'description',
        'journalable_id',
        'journalable_type',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function journalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function items(): HasMany
    {
        return $this->hasMany(JournalItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
