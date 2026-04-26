<?php

namespace App\Http\Controllers;

use App\Actions\RecordStockMovement;
use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\ProductPriceStat;
use App\Models\Produk;
use App\Models\StockOpname;
use App\Services\JournalService;
use App\Services\StornoService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class StockOpnameController extends Controller
{
    public function __construct(
        protected StornoService $stornoService
    ) {}

    public function index(Request $request): Response
    {
        $query = StockOpname::withCount('items');

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('keterangan', 'like', "%{$request->search}%");
        }

        $perPage = $request->input('per_page', 10);

        return Inertia::render('stock-opname/Index', [
            'opnames' => $query->latest('tanggal')->latest('id')->paginate($perPage)->withQueryString(),
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function create(Request $request): Response
    {
        $query = Produk::with(['stock', 'satuan']);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $produks = $query->paginate(10)->withQueryString();

        return Inertia::render('stock-opname/Create', [
            'produks' => $produks,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
            'items.*.harga_satuan' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated) {
            $opname = StockOpname::create([
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => $validated['status'],
            ]);

            foreach ($validated['items'] as &$item) {
                if (! isset($item['harga_satuan']) || $item['harga_satuan'] === null) {
                    $avgPrice = ProductPriceStat::where('produk_id', $item['produk_id'])->value('avg_price');
                    if ($avgPrice === null) {
                        Log::warning("harga_satuan not found for produk_id {$item['produk_id']}");
                        $item['harga_satuan'] = 0;
                    } else {
                        $item['harga_satuan'] = (int) round(((float) $avgPrice) * 100);
                    }
                } else {
                    $item['harga_satuan'] = (int) round(((float) $item['harga_satuan']) * 100);
                }
                $opname->items()->create($item);
            }

            if ($validated['status'] === 'completed') {
                $opname->refresh();
                $this->finalizeOpname($opname);
            }
        });

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil disimpan.');
    }

    public function show(StockOpname $stockOpname): Response
    {
        $stockOpname->load(['items.produk.satuan', 'items.satuan']);

        return Inertia::render('stock-opname/Show', [
            'opname' => $stockOpname,
        ]);
    }

    public function edit(Request $request, StockOpname $stockOpname): Response|RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->route('stock-opname.show', $stockOpname);
        }

        $stockOpname->load('items');

        $query = Produk::with(['stock', 'satuan']);

        if ($request->has('search') && ! empty($request->search)) {
            $query->where('nama', 'like', "%{$request->search}%");
        }

        $produks = $query->paginate(10)->withQueryString();

        return Inertia::render('stock-opname/Edit', [
            'opname' => $stockOpname,
            'produks' => $produks,
            'filters' => $request->only(['search']),
        ]);
    }

    public function update(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->route('stock-opname.show', $stockOpname);
        }

        $validated = $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string',
            'status' => 'required|in:draft,completed',
            'items' => 'required|array',
            'items.*.produk_id' => 'required|exists:produks,id',
            'items.*.satuan_id' => 'required|exists:satuans,id',
            'items.*.system_qty' => 'required|numeric',
            'items.*.physical_qty' => 'required|numeric',
            'items.*.harga_satuan' => 'nullable|numeric',
        ]);

        DB::transaction(function () use ($validated, $stockOpname) {
            $stockOpname->update([
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => $validated['status'],
            ]);

            $stockOpname->items()->delete();
            foreach ($validated['items'] as &$item) {
                if (! isset($item['harga_satuan']) || $item['harga_satuan'] === null) {
                    $avgPrice = ProductPriceStat::where('produk_id', $item['produk_id'])->value('avg_price');
                    if ($avgPrice === null) {
                        Log::warning("harga_satuan not found for produk_id {$item['produk_id']}");
                        $item['harga_satuan'] = 0;
                    } else {
                        $item['harga_satuan'] = (int) round(((float) $avgPrice) * 100);
                    }
                } else {
                    $item['harga_satuan'] = (int) round(((float) $item['harga_satuan']) * 100);
                }
                $stockOpname->items()->create($item);
            }

            if ($validated['status'] === 'completed') {
                $stockOpname->refresh();
                $this->finalizeOpname($stockOpname);
            }
        });

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil diperbarui.');
    }

    public function destroy(StockOpname $stockOpname): RedirectResponse
    {
        if ($stockOpname->status === 'completed') {
            return redirect()->back()->with('error', 'Stock opname yang sudah selesai tidak dapat dihapus.');
        }

        $stockOpname->delete();

        return redirect()->route('stock-opname.index')->with('success', 'Stock opname berhasil dihapus.');
    }

    public function stornoOpname(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'nullable|string|max:255',
            ]);

            Log::info("Performing Storno for OPN-{$stockOpname->id}");
            $this->stornoService->perform($stockOpname, $validated['reason'] ?? 'Dibatalkan oleh pengguna');

            return redirect()->back()->with('success', 'Hasil opname berhasil di-storno (reversal).');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal melakukan storno: '.$e->getMessage());
        }
    }

    public function reopen(Request $request, StockOpname $stockOpname): RedirectResponse
    {
        try {
            DB::transaction(function () use ($stockOpname) {
                // 1. Perform reversal (storno)
                $this->stornoService->perform($stockOpname, 'Dibuka kembali untuk pengeditan');

                // 2. Set back to draft
                $stockOpname->update([
                    'status' => 'draft',
                    'storno_at' => null,
                    'storno_reason' => null,
                ]);
            });

            return redirect()->route('stock-opname.edit', $stockOpname)->with('success', 'Opname telah dikembalikan ke Draft untuk diedit.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal membuka kembali: '.$e->getMessage());
        }
    }

    private function finalizeOpname(StockOpname $opname): void
    {
        foreach ($opname->items as $item) {
            $diff = (float) $item->physical_qty - (float) $item->system_qty;
            if (abs($diff) > 0.000001) {
                app(RecordStockMovement::class)->handle([
                    'produk_id' => $item->produk_id,
                    'satuan_id' => $item->satuan_id,
                    'type' => $diff > 0 ? 'in' : 'out',
                    'jumlah' => abs($diff),
                    'reference_type' => 'stock_opname',
                    'reference_id' => $opname->id,
                    'keterangan' => 'Penyesuaian stok dari Opname #'.$opname->id.' tgl '.($opname->tanggal instanceof Carbon ? $opname->tanggal->format('d/m/Y') : $opname->tanggal),
                ]);

                // Journal Logic
                try {
                    $diffQty = (float) $item->physical_qty - (float) $item->system_qty;
                    $hargaSatuan = (int) ($item->harga_satuan ?? 0);
                    $nilaiSelisih = (int) round(abs($diffQty) * $hargaSatuan);

                    if ($nilaiSelisih > 0) {
                        $journalService = app(JournalService::class);
                        $refNumber = 'OPN-'.($opname->tanggal instanceof Carbon ? $opname->tanggal->format('Ymd') : date('Ymd', strtotime((string) $opname->tanggal)))."-{$opname->id}-{$item->id}";

                        $persediaanAccount = Account::where('code', '1301')->first();
                        $itemsData = [];

                        if ($diffQty > 0) {
                            // Surplus
                            $incomeAccount = Account::where('code', '4102')->first();
                            if ($persediaanAccount && $incomeAccount) {
                                $itemsData = [
                                    new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'debit'),
                                    new JournalItemData($incomeAccount->id, $nilaiSelisih, 'credit'),
                                ];
                            }
                        } else {
                            // Shrinkage
                            $expenseAccount = Account::where('code', '6201')->first();
                            if ($persediaanAccount && $expenseAccount) {
                                $itemsData = [
                                    new JournalItemData($expenseAccount->id, $nilaiSelisih, 'debit'),
                                    new JournalItemData($persediaanAccount->id, $nilaiSelisih, 'credit'),
                                ];
                            }
                        }

                        if (! empty($itemsData)) {
                            $journalData = new JournalEntryData(
                                items: $itemsData,
                                description: "Penyesuaian stok Opname #{$opname->id} item {$item->produk_id}",
                                tanggal: $opname->tanggal,
                                ref_number: $refNumber,
                                journalable: $opname
                            );
                            $journalService->record($journalData);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to record journal for Opname item #{$item->id}: ".$e->getMessage());
                }
            }
        }
    }
}
