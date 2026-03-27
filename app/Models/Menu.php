<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'route_name',
        'path',
        'icon',
        'permission_name',
        'group_name',
        'order_priority',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_priority' => 'integer',
    ];

    /**
     * Get the parent menu.
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * Get the child menus.
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id')->orderBy('order_priority');
    }

    /**
     * Scope for root menus.
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for active menus.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the roles that have access to this menu.
     */
    public function roles(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
