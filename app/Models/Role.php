<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    /**
     * Get the menus for the role.
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class);
    }
}
