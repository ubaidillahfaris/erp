<?php

namespace App\Observers;

use App\Models\Product;

class ProductObserver
{
    /**
     * Handle the Product "saving" event.
     */
    public function saving(Product $product): void
    {
        if (empty($product->sku)) {
            $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 3));
            if (empty($prefix)) {
                $prefix = 'PRD';
            }

            $lastProduct = Product::where('sku', 'like', $prefix.'-%')
                ->latest('id')
                ->first();

            $sequence = 1;
            if ($lastProduct) {
                $lastSequence = (int) substr($lastProduct->sku, -4);
                $sequence = $lastSequence + 1;
            }

            $product->sku = $prefix.'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        }

        $product->sku = strtoupper($product->sku);
    }

    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        //
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        //
    }
}
