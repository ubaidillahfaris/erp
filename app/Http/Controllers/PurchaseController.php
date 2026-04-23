<?php

namespace App\Http\Controllers;

use App\Actions\Purchasing\FinalizePurchase;
use App\Http\Requests\StorePurchaseRequest;
use App\Models\Produk;
use App\Models\Purchase;
use App\Models\PurchaseAttachment;
use App\Models\Satuan;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort') ?: 'tanggal';
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        // Handle faceted filters
        $activeFilters = $request->input('active_filters', []);
        $type = $activeFilters['transaction_type'] ?? $request->input('type');
        $status = $activeFilters['status'] ?? $request->input('status');

        $query = Purchase::with(['vendor'])->withCount('items');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('keterangan', 'like', "%{$search}%")
                    ->orWhere('no_invoice', 'like', "%{$search}%")
                    ->orWhereHas('vendor', fn ($qv) => $qv->where('nama', 'like', "%{$search}%"));
            });
        }

        if ($type && $type !== 'semua') {
            if (is_array($type)) {
                $query->whereIn('transaction_type', $type);
            } else {
                $query->where('transaction_type', $type);
            }
        }

        if ($status && $status !== 'semua') {
            if (is_array($status)) {
                $query->whereIn('status', $status);
            } else {
                $query->where('status', $status);
            }
        }

        $purchases = $query->orderBy($sort, $direction)->paginate($perPage)->withQueryString();

        return Inertia::render('purchasing/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['search', 'active_filters', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('purchasing/Create', [
            'produks' => Produk::with(['satuan', 'currentPrice'])->where('is_active', true)->get(),
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'vendors' => Vendor::orderBy('nama')->get(['id', 'nama']),
            'produkId' => $request->query('produk_id'),
        ]);
    }

    public function store(StorePurchaseRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $itemsTotal = collect($request->items)->sum(fn ($item) => $item['jumlah'] * $item['harga_satuan']);

            $purchase = Purchase::create([
                'no_invoice' => $request->no_invoice,
                'vendor_id' => $request->vendor_id,
                'tanggal' => $request->tanggal,
                'transaction_type' => $request->transaction_type,
                'payment_method' => $request->payment_method,
                'status' => 'draft',
                'total_biaya' => $itemsTotal,
                'keterangan' => $request->keterangan,
            ]);

            foreach ($request->items as $item) {
                $purchase->items()->create($item);
            }

            // Handle multi-file attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('purchases/attachments', 'private');
                    PurchaseAttachment::create([
                        'id' => Str::uuid()->toString(),
                        'purchase_id' => $purchase->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()->route('purchasing.index')->with('success', 'Pembelian berhasil disimpan sebagai Draft.');
    }

    public function edit(Purchase $purchase): Response|RedirectResponse
    {
        if ($purchase->status === 'finalized') {
            return redirect()->route('purchasing.index')->with('error', 'Pembelian yang sudah difinalisasi tidak dapat diedit.');
        }

        $purchase->load(['items', 'attachments']);

        return Inertia::render('purchasing/Edit', [
            'purchase' => $purchase,
            'produks' => Produk::with(['satuan', 'currentPrice'])->where('is_active', true)->get(),
            'satuans' => Satuan::all(['id', 'nama', 'simbol']),
            'vendors' => Vendor::orderBy('nama')->get(['id', 'nama']),
        ]);
    }

    public function update(StorePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->status === 'finalized') {
            return redirect()->route('purchasing.index')->with('error', 'Pembelian yang sudah difinalisasi tidak dapat diedit.');
        }

        DB::transaction(function () use ($request, $purchase) {
            $itemsTotal = collect($request->items)->sum(fn ($item) => $item['jumlah'] * $item['harga_satuan']);

            $purchase->update([
                'no_invoice' => $request->no_invoice,
                'vendor_id' => $request->vendor_id,
                'tanggal' => $request->tanggal,
                'transaction_type' => $request->transaction_type,
                'payment_method' => $request->payment_method,
                'total_biaya' => $itemsTotal,
                'keterangan' => $request->keterangan,
            ]);

            // Sync items (delete existing and recreate is simplest)
            $purchase->items()->delete();
            foreach ($request->items as $item) {
                $purchase->items()->create($item);
            }

            // Handle new multi-file attachments (append)
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('purchases/attachments', 'private');
                    PurchaseAttachment::create([
                        'id' => Str::uuid()->toString(),
                        'purchase_id' => $purchase->id,
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        });

        return redirect()->route('purchasing.show', $purchase)->with('success', 'Draft Pembelian berhasil diperbarui.');
    }

    public function show(Purchase $purchase): Response
    {
        $purchase->load(['items.produk.satuan', 'items.satuan', 'vendor', 'attachments']);

        return Inertia::render('purchasing/Show', [
            'purchase' => $purchase,
        ]);
    }

    public function finalize(Purchase $purchase, FinalizePurchase $action, Request $request): RedirectResponse
    {
        $signatureMetadata = [
            'user_id' => $request->user()->id,
            'user_name' => $request->user()->name,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'finalized_at' => now()->toISOString(),
        ];

        $action->handle($purchase, $signatureMetadata);

        return redirect()->route('purchasing.index')->with('success', 'Pembelian berhasil difinalisasi. Stok dan harga telah diperbarui.');
    }

    public function destroy(Purchase $purchase): RedirectResponse
    {
        if ($purchase->status === 'finalized') {
            return redirect()->back()->with('error', 'Pembelian yang sudah difinalisasi tidak dapat dihapus.');
        }

        // Cleanup attachments from storage
        foreach ($purchase->attachments as $attachment) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $purchase->delete();

        return redirect()->route('purchasing.index')->with('success', 'Pembelian berhasil dihapus.');
    }

    public function destroyAttachment(PurchaseAttachment $purchaseAttachment): RedirectResponse
    {
        Storage::disk('private')->delete($purchaseAttachment->file_path);
        $purchaseAttachment->delete();

        return redirect()->back()->with('success', 'Lampiran berhasil dihapus.');
    }
}
