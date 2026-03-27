<?php

namespace App\Observers;

use App\Models\Satuan;

class SatuanObserver
{
    /**
     * Handle the Satuan "created" event.
     */
    public function created(Satuan $satuan): void
    {
        //
    }

    /**
     * Handle the Satuan "updated" event.
     */
    public function updated(Satuan $satuan): void
    {
        //
    }

    /**
     * Handle the Satuan "deleted" event.
     */
    public function deleted(Satuan $satuan): void
    {
        //
    }

    /**
     * Handle the Satuan "restored" event.
     */
    public function restored(Satuan $satuan): void
    {
        //
    }

    /**
     * Handle the Satuan "force deleted" event.
     */
    public function forceDeleted(Satuan $satuan): void
    {
        //
    }
}
