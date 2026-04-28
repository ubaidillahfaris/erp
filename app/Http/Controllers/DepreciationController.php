<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DepreciationSchedule;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepreciationController extends Controller
{
    public function __construct(
        private DepreciationService $depreciationService
    ) {}

    public function index(Request $request): Response
    {
        $periods = DepreciationSchedule::select('period_month', 'period_year', 'status', \DB::raw('count(*) as asset_count'), \DB::raw('sum(depreciation_amount) as total_amount'))
            ->groupBy('period_month', 'period_year', 'status')
            ->orderBy('period_year', 'desc')
            ->orderBy('period_month', 'desc')
            ->get();

        return Inertia::render('accounting/Depreciation', [
            'periods' => $periods,
        ]);
    }

    public function post(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2000',
        ]);

        try {
            $count = $this->depreciationService->postPeriod((int) $request->month, (int) $request->year);

            if ($count === 0) {
                return redirect()->back()->with('warning', 'Tidak ada penyusutan yang dijadwalkan untuk periode ini.');
            }

            return redirect()->back()->with('success', "Berhasil memproses penyusutan untuk {$count} aset.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
