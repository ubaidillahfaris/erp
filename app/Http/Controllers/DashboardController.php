<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\FixedAsset;
use App\Models\Pengeluaran;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = Carbon::today();
        $interval = $request->query('interval', 'D');

        // 1. Total Penjualan Hari Ini
        $salesToday = Sale::whereDate('date', $today)->sum('total_amount');

        // 2. Productsi Aktif
        $activeProductions = Production::where('status', '!=', 'selesai')->count();

        // 3. Stok Kritis
        $criticalStockCount = Stock::whereHas('product', function ($q) {
            $q->where('track_stock', true);
        })->where('balance', '<=', 10)->count();

        // 4. Pengeluaran Hari Ini
        $expensesToday = Pengeluaran::whereDate('date', $today)->sum('nominal');

        // Recent Sales
        $recentSales = Sale::latest()->take(5)->get();

        // Vendors with location
        $vendors = Vendor::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // 5. Shared Chart Configuration Grouping (H, D, W, M, Y)
        $chartConfig = match ($interval) {
            'H' => [
                'range' => now()->subHours(24),
                'col' => 'created_at',
                'trunc' => 'hour',
            ],
            'W' => [
                'range' => now()->subWeeks(12),
                'col' => 'date',
                'trunc' => 'week',
            ],
            'M' => [
                'range' => now()->subMonths(12),
                'col' => 'date',
                'trunc' => 'month',
            ],
            'Y' => [
                'range' => now()->subYears(5),
                'col' => 'date',
                'trunc' => 'year',
            ],
            default => [
                'range' => now()->subDays(30),
                'col' => 'date',
                'trunc' => 'day',
            ],
        };

        // Revenue Flow (Sum)
        $dbDriver = DB::connection()->getDriverName();
        $trunc = $chartConfig['trunc'];
        $col = $chartConfig['col'];

        if ($dbDriver === 'sqlite') {
            $format = match ($trunc) {
                'hour' => '%Y-%m-%d %H:00:00',
                'day' => '%Y-%m-%d',
                'week' => '%Y-%W',
                'month' => '%Y-%m',
                'year' => '%Y',
                default => '%Y-%m-%d'
            };
            $selectRaw = "strftime('$format', $col) as date";
        } else {
            $selectRaw = "DATE_TRUNC('$trunc', $col) as date";
        }

        $salesTrend = DB::table('sales')
            ->where($chartConfig['col'], '>=', $chartConfig['range'])
            ->selectRaw("$selectRaw, SUM(total_amount) as total")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $expensesTrend = DB::table('pengeluarans')
            ->where($chartConfig['col'], '>=', $chartConfig['range'])
            ->selectRaw("$selectRaw, SUM(nominal) as total")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $allDates = collect([...$salesTrend->keys(), ...$expensesTrend->keys()])
            ->unique()
            ->sort()
            ->values();

        $cashFlowTrend = $allDates->map(function ($date) use ($salesTrend, $expensesTrend) {
            return [
                'month' => $date, // Standardized date key, will be formatted in Vue
                'income' => (float) ($salesTrend->get($date)->total ?? 0),
                'expense' => (float) ($expensesTrend->get($date)->total ?? 0),
            ];
        });

        // Traffic Pulse (Count)
        $pulseData = DB::table('sales')
            ->where($chartConfig['col'], '>=', $chartConfig['range'])
            ->selectRaw("$selectRaw, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn ($item) => [
                'date' => $item->date,
                'count' => (int) $item->count,
            ]);

        // 6. Recent Audit Logs
        $recentAudits = AuditLog::with(['user', 'auditable'])
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('dashboard/Dashboard', [
            'metrics' => [
                'sales_today' => (float) $salesToday,
                'active_productions' => $activeProductions,
                'critical_stocks' => $criticalStockCount,
                'expenses_today' => (float) $expensesToday,
                'asset_value' => (float) (FixedAsset::where('status', '!=', 'disposed')->sum('current_book_value') / 100),
                'fully_depreciated' => FixedAsset::where('status', 'fully_depreciated')->count(),
            ],
            'recent_sales' => $recentSales,
            'vendors' => $vendors,
            'cash_flow_trend' => $cashFlowTrend,
            'heatmap_data' => $pulseData,
            'recent_audits' => $recentAudits,
            'current_interval' => $interval,
        ]);
    }
}
