<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    use Auditable;

    protected $fillable = [
        'slug',
        'name',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(TierFeature::class);
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }
}
