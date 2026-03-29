<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\FinancialSummary;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class JournalController extends Controller
{
    public function index(Request $request): Response
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfDay()->format('Y-m-d'));

        $perPage = $request->integer('per_page', 10);
        $sort = $request->input('sort') ?: 'tanggal';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $journals = Journal::query()
            ->with(['reference'])
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy($sort, $direction)
            ->orderBy('id', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $summaries = FinancialSummary::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->get();

        return Inertia::render('Journal/Index', [
            'journals' => $journals,
            'summaries' => $summaries,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'per_page' => $perPage,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }
}
