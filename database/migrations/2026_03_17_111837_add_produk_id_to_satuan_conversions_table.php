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
        Schema::table('satuan_conversions', function (Blueprint $table) {
            $table->foreignId('produk_id')->nullable()->after('to_satuan_id')->constrained('produks')->onDelete('cascade');
            $table->index(['produk_id', 'satuan_id', 'to_satuan_id'], 'idx_p_s_ts');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('satuan_conversions', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropColumn('produk_id');
        });
    }
};
