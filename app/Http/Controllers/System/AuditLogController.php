<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class AuditLogController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:superadmin'),
        ];
    }

    /**
     * Display a listing of audit logs.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $query = AuditLog::with('user')
            ->latest();

        // Filters
        $query->when($request->search, function ($query, $search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhere('event', 'like', "%{$search}%")
                ->orWhere('auditable_type', 'like', "%{$search}%")
                ->orWhere('auditable_id', 'like', "%{$search}%");
        });

        $query->when($request->event, function ($query, $event) {
            $query->where('event', $event);
        });

        $query->when($request->user_id, function ($query, $userId) {
            $query->where('user_id', $userId);
        });

        $logs = $query->paginate($perPage)->withQueryString();

        // Get unique events and users for filters
        $events = AuditLog::select('event')->distinct()->pluck('event');
        $users = User::select('id', 'name')->get();

        return Inertia::render('System/AuditLog/Index', [
            'logs' => $logs,
            'events' => $events,
            'users' => $users,
            'filters' => $request->only(['search', 'event', 'user_id', 'per_page']),
        ]);
    }
}
