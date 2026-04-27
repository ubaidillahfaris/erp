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
        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('target_unit_id')->constrained('products')->cascadeOnDelete();
            $table->index(['product_id', 'unit_id', 'target_unit_id'], 'idx_p_u_tu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_conversions', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
