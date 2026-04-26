<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            static::logChange($model, 'created');
        });

        static::updated(function ($model) {
            static::logChange($model, 'updated');
        });

        static::deleted(function ($model) {
            static::logChange($model, 'deleted');
        });
    }

    protected static function logChange($model, $event)
    {
        AuditLog::create([
            'user_id' => Auth::id(),
            'event' => $event,
            'auditable_id' => $model->id,
            'auditable_type' => get_class($model),
            'old_values' => $event === 'updated' ? $model->getOriginal() : null,
            'new_values' => $event === 'created' ? $model->getAttributes() : ($event === 'updated' ? $model->getChanges() : null),
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
