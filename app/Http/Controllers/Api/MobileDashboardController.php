<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengeluaran;
use App\Models\Sale;
use Carbon\Carbon;

/**
 * @group Owner Dashboard
 *
 * API untuk melihat ringkasan performa warung secara real-time.
 */
class MobileDashboardController extends Controller
{
    /**
     * Ringkasan Dashboard (Harian)
     *
     * Mengambil total penjualan, jumlah transaksi, dan estimasi laba kotor hari ini.
     */
    public function summary()
    {
        $today = Carbon::today();

        $salesToday = Sale::whereDate('tanggal', $today)->get();
        $totalSales = $salesToday->sum('total_amount');
        $transactionCount = $salesToday->count();

        // Estimasi Laba Kotor (Total Jual - Total Modal dari items)
        $grossProfit = $salesToday->sum(function ($sale) {
            return $sale->items->sum(function ($item) {
                return $item->subtotal - ($item->qty * $item->cost);
            });
        });

        $expensesToday = Pengeluaran::whereDate('tanggal', $today)->sum('nominal');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $today->toDateString(),
                'total_sales' => (float) $totalSales,
                'transaction_count' => $transactionCount,
                'gross_profit' => (float) $grossProfit,
                'total_expenses' => (float) $expensesToday,
                'net_income_estimate' => (float) ($grossProfit - $expensesToday),
            ],
        ]);
    }
}
