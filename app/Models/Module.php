<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'order_priority',
        'is_active',
    ];

    public function menus(): HasMany
    {
        return $this->hasMany(Menu::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'module_role');
    }

    /**
     * Scope a query to only include active modules.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
