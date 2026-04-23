<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProfitLossController extends Controller
{
    public function index(Request $request): Response
    {
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfDay()->format('Y-m-d'));

        // Aggregations
        $financialData = Journal::whereBetween('tanggal', [$startDate, $endDate])
            ->select('category', 'type', DB::raw('SUM(amount) as total'))
            ->groupBy('category', 'type')
            ->get();

        $report = [
            'revenue' => [
                'total' => 0,
                'items' => [],
            ],
            'cogs' => [
                'total' => 0,
                'items' => [],
            ],
            'expenses' => [
                'total' => 0,
                'items' => [],
            ],
        ];

        foreach ($financialData as $data) {
            $label = ucfirst($data->category);

            if ($data->category === 'hpp') {
                $label = 'Beban Pokok Penjualan (HPP)';
            } elseif ($data->category === 'produksi') {
                $label = 'Harga Pokok Produksi';
            } elseif ($data->category === 'penjualan') {
                $label = 'Pendapatan Penjualan';
            }

            $item = [
                'label' => $label,
                'amount' => (float) $data->total,
            ];

            if ($data->category === 'penjualan') {
                $report['revenue']['total'] += $item['amount'];
                $report['revenue']['items'][] = $item;
            } elseif (in_array($data->category, ['hpp', 'produksi'])) {
                $report['cogs']['total'] += $item['amount'];
                $report['cogs']['items'][] = $item;
            } elseif ($data->category === 'persediaan') {
                // Asset acquisition (Restock) - skip from P/L calculation
                continue;
            } else {
                // Everything else is treated as operational expense
                $report['expenses']['total'] += $item['amount'];
                $report['expenses']['items'][] = $item;
            }
        }

        $grossProfit = $report['revenue']['total'] - $report['cogs']['total'];
        $netProfit = $grossProfit - $report['expenses']['total'];

        return Inertia::render('ProfitLoss/Index', [
            'report' => $report,
            'summary' => [
                'gross_profit' => $grossProfit,
                'net_profit' => $netProfit,
                'margin' => $report['revenue']['total'] > 0 ? ($netProfit / $report['revenue']['total']) * 100 : 0,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
