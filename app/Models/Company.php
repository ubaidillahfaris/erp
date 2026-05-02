<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\Auditable;
use App\Traits\HasFeatures;

class Company extends Model
{
    use HasFactory, HasFeatures, Auditable;

    protected $fillable = [
        'name',
        'business_type',
        'tier_id',
        'owner_id',
        'address',
        'phone',
        'logo',
    ];

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'company_modules')
            ->withPivot('is_active', 'expires_at')
            ->withTimestamps();
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
