<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Journal;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfitLossController extends Controller
{
    /**
     * Display profit and loss report.
     */
    public function index(Request $request): JsonResponse
    {
        $startDate = $request->query('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', Carbon::now()->endOfMonth()->toDateString());

        $summary = Journal::whereBetween('date', [$startDate, $endDate])
            ->selectRaw("SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as total_income")
            ->selectRaw("SUM(CASE WHEN type = 'kredit' THEN amount ELSE 0 END) as total_expense")
            ->first();

        $categoryBreakdown = Journal::whereBetween('date', [$startDate, $endDate])
            ->select('category', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category', 'type')
            ->get();

        $incomeBreakdown = $categoryBreakdown->where('type', 'debit')->values();
        $expenseBreakdown = $categoryBreakdown->where('type', 'kredit')->values();

        $totalIncome = (float) $summary->total_income;
        $totalExpense = (float) $summary->total_expense;
        $netProfit = $totalIncome - $totalExpense;

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => [
                    'total_income' => $totalIncome,
                    'total_expense' => $totalExpense,
                    'net_profit' => $netProfit,
                ],
                'breakdown' => [
                    'income' => $incomeBreakdown,
                    'expense' => $expenseBreakdown,
                ],
            ],
        ]);
    }
}
