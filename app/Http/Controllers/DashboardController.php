<?php

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use App\Models\Production;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Vendor;
use Carbon\Carbon;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        // 1. Total Penjualan Hari Ini
        $salesToday = Sale::whereDate('tanggal', $today)->sum('total_amount');

        // 2. Produksi Aktif (Status selain 'Selesai' atau 'Batal' dll. Asumsi status: 'Draft', 'Proses')
        $activeProductions = Production::where('status', '!=', 'selesai')->count();

        // 3. Stok Kritis: Produk dengan balance <= 10
        $criticalStockCount = Stock::whereHas('produk', function ($q) {
            $q->where('track_stock', true);
        })->where('balance', '<=', 10)->count();

        // 4. Pengeluaran Hari Ini
        $expensesToday = Pengeluaran::whereDate('tanggal', $today)->sum('nominal');

        // Recent Sales untuk data list
        $recentSales = Sale::latest()->take(5)->get();

        // Vendors with location
        $vendors = Vendor::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        return Inertia::render('Dashboard', [
            'metrics' => [
                'sales_today' => (float) $salesToday,
                'active_productions' => $activeProductions,
                'critical_stocks' => $criticalStockCount,
                'expenses_today' => (float) $expensesToday,
            ],
            'recent_sales' => $recentSales,
            'vendors' => $vendors,
        ]);
    }
}
