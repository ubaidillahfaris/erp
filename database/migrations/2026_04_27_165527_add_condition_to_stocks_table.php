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
        Schema::table('stocks', function (Blueprint $table) {
            $table->enum('condition', ['good', 'damaged', 'refurbished', 'quarantine'])->default('good')->after('warehouse_id');
            $table->boolean('is_sellable')->default(true)->after('condition');

            // Drop existing unique constraint
            $table->dropUnique(['product_id', 'warehouse_id']);

            // Add new composite unique constraint
            $table->unique(['product_id', 'warehouse_id', 'condition']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->enum('condition', ['good', 'damaged', 'refurbished', 'quarantine'])->default('good')->after('warehouse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropColumn('condition');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'warehouse_id', 'condition']);
            $table->unique(['product_id', 'warehouse_id']);
            $table->dropColumn(['condition', 'is_sellable']);
        });
    }
};
