<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Any COGS (pembelian with Sale reference) becomes 'hpp'
        \App\Models\Journal::where('category', 'pembelian')
            ->where('reference_type', \App\Models\Sale::class)
            ->update(['category' => 'hpp']);

        // 2. Any Restock (pembelian with Restock reference) becomes 'persediaan'
        \App\Models\Journal::where('category', 'pembelian')
            ->where('reference_type', \App\Models\Restock::class)
            ->update(['category' => 'persediaan']);
    }

    public function down(): void
    {
        \App\Models\Journal::where('category', 'hpp')->update(['category' => 'pembelian']);
        \App\Models\Journal::where('category', 'persediaan')->update(['category' => 'pembelian']);
    }
};
