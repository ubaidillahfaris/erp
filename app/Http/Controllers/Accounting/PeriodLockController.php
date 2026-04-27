<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\PeriodLock;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Inertia\Inertia;

class PeriodLockController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('role:superadmin'),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $periods = PeriodLock::orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        // Generate suggested periods for the dropdown (last 12 months)
        $suggestions = [];
        $now = Carbon::now();
        for ($i = 0; $i < 12; $i++) {
            $date = $now->copy()->subMonths($i);
            $suggestions[] = [
                'month' => $date->month,
                'year' => $date->year,
                'label' => $date->format('F Y'),
            ];
        }

        return Inertia::render('accounting/Periods/Index', [
            'periods' => $periods,
            'suggestions' => $suggestions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020',
        ]);

        // Check if exists
        $exists = PeriodLock::where('period_month', $request->period_month)
            ->where('period_year', $request->period_year)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Periode ini sudah terdaftar.');
        }

        PeriodLock::create([
            'period_month' => $request->period_month,
            'period_year' => $request->period_year,
            'is_locked' => true, // Default to locked when adding
        ]);

        return back()->with('success', 'Periode berhasil dikunci.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PeriodLock $period)
    {
        $request->validate([
            'is_locked' => 'required|boolean',
        ]);

        $period->update([
            'is_locked' => $request->is_locked,
        ]);

        $status = $request->is_locked ? 'dikunci' : 'dibuka';
        $monthName = Carbon::createFromDate($period->period_year, $period->period_month, 1)->format('F');

        return back()->with('success', "Periode {$monthName} {$period->period_year} berhasil {$status}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PeriodLock $period)
    {
        $period->delete();

        return back()->with('success', 'Periode berhasil dihapus dari daftar kontrol.');
    }
}
