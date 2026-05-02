<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFeatureOverride extends Model
{
    use Auditable;

    protected $fillable = [
        'company_id',
        'feature_key',
        'is_enabled',
        'expires_at',
        'reason',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
