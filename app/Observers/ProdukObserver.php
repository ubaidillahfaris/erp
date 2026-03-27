<?php

namespace App\Observers;

use App\Models\Produk;

class ProdukObserver
{
    /**
     * Handle the Produk "saving" event.
     */
    public function saving(Produk $produk): void
    {
        if (empty($produk->sku)) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $produk->nama), 0, 3));
            if (empty($prefix)) {
                $prefix = 'PRD';
            }

            $lastProduk = Produk::where('sku', 'like', $prefix.'-%')
                ->latest('id')
                ->first();

            $sequence = 1;
            if ($lastProduk) {
                $lastSequence = (int) substr($lastProduk->sku, -4);
                $sequence = $lastSequence + 1;
            }

            $produk->sku = $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        $produk->sku = strtoupper($produk->sku);
    }

    /**
     * Handle the Produk "created" event.
     */
    public function created(Produk $produk): void
    {
        //
    }

    /**
     * Handle the Produk "updated" event.
     */
    public function updated(Produk $produk): void
    {
        //
    }

    /**
     * Handle the Produk "deleted" event.
     */
    public function deleted(Produk $produk): void
    {
        //
    }

    /**
     * Handle the Produk "restored" event.
     */
    public function restored(Produk $produk): void
    {
        //
    }

    /**
     * Handle the Produk "force deleted" event.
     */
    public function forceDeleted(Produk $produk): void
    {
        //
    }
}
