<?php

namespace App\Http\Controllers;

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
        $salesToday = Sale::whereDate('tanggal', $today)->sum('total_amount');

        // 2. Produksi Aktif
        $activeProductions = Production::where('status', '!=', 'selesai')->count();

        // 3. Stok Kritis
        $criticalStockCount = Stock::whereHas('produk', function ($q) {
            $q->where('track_stock', true);
        })->where('balance', '<=', 10)->count();

        // 4. Pengeluaran Hari Ini
        $expensesToday = Pengeluaran::whereDate('tanggal', $today)->sum('nominal');

        // Recent Sales
        $recentSales = Sale::latest()->take(5)->get();

        // Vendors with location
        $vendors = Vendor::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        // 5. Shared Chart Configuration Grouping (H, D, W, M, Y)
        $chartConfig = match($interval) {
            'H' => [
                'range' => now()->subHours(24),
                'col' => 'created_at',
                'trunc' => 'hour'
            ],
            'W' => [
                'range' => now()->subWeeks(12),
                'col' => 'tanggal',
                'trunc' => 'week'
            ],
            'M' => [
                'range' => now()->subMonths(12),
                'col' => 'tanggal',
                'trunc' => 'month'
            ],
            'Y' => [
                'range' => now()->subYears(5),
                'col' => 'tanggal',
                'trunc' => 'year'
            ],
            default => [
                'range' => now()->subDays(30),
                'col' => 'tanggal',
                'trunc' => 'day'
            ],
        };

        // Revenue Flow (Sum)
        $salesTrend = DB::table('sales')
            ->where($chartConfig['col'], '>=', $chartConfig['range'])
            ->selectRaw("DATE_TRUNC('{$chartConfig['trunc']}', {$chartConfig['col']}) as date, SUM(total_amount) as total")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'total' => (float) $item->total
            ]);

        // Traffic Pulse (Count)
        $pulseData = DB::table('sales')
            ->where($chartConfig['col'], '>=', $chartConfig['range'])
            ->selectRaw("DATE_TRUNC('{$chartConfig['trunc']}', {$chartConfig['col']}) as date, COUNT(*) as count")
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->map(fn($item) => [
                'date' => $item->date,
                'count' => (int) $item->count
            ]);

        return Inertia::render('Dashboard', [
            'metrics' => [
                'sales_today' => (float) $salesToday,
                'active_productions' => $activeProductions,
                'critical_stocks' => $criticalStockCount,
                'expenses_today' => (float) $expensesToday,
            ],
            'recent_sales' => $recentSales,
            'vendors' => $vendors,
            'sales_trend' => $salesTrend,
            'heatmap_data' => $pulseData,
            'current_interval' => $interval,
        ]);
    }
}
