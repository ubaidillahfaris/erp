<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TierFeature extends Model
{
    use Auditable;

    protected $fillable = [
        'tier_id',
        'feature_key',
        'module_id',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
