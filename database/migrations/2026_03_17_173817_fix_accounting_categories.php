<?php

use App\Models\Journal;
use App\Models\Restock;
use App\Models\Sale;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Any COGS (pembelian with Sale reference) becomes 'hpp'
        Journal::where('category', 'pembelian')
            ->where('reference_type', Sale::class)
            ->update(['category' => 'hpp']);

        // 2. Any Restock (pembelian with Restock reference) becomes 'persediaan'
        Journal::where('category', 'pembelian')
            ->where('reference_type', Restock::class)
            ->update(['category' => 'persediaan']);
    }

    public function down(): void
    {
        Journal::where('category', 'hpp')->update(['category' => 'pembelian']);
        Journal::where('category', 'persediaan')->update(['category' => 'pembelian']);
    }
};
