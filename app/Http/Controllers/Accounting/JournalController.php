<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page', 10);
        $sort = $request->input('sort') ?: 'tanggal';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = JournalEntry::query()
            ->with(['items.account', 'journalable', 'creator'])
            ->withSum('items', 'debit')
            ->withSum('items', 'credit');

        // Filters
        $query->when($request->search, function ($q, $search) {
            $q->where('ref_number', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });

        $query->when($request->date_start, function ($q, $date) {
            $q->whereDate('tanggal', '>=', $date);
        });

        $query->when($request->date_end, function ($q, $date) {
            $q->whereDate('tanggal', '<=', $date);
        });

        $query->when($request->type, function ($q, $type) {
            $modelMap = [
                'SALE' => 'App\Models\Sale',
                'PRD' => 'App\Models\Production',
                'PUR' => 'App\Models\Purchase',
            ];
            
            if (isset($modelMap[strtoupper($type)])) {
                $q->where('journalable_type', $modelMap[strtoupper($type)]);
            }
        });

        $journals = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('accounting/Journal', [
            'journals' => $journals,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'type', 'per_page', 'sort', 'direction']),
        ]);
    }
}
