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
        // 1. Create a default warehouse if it doesn't exist (to handle existing data)
        $defaultWarehouseId = DB::table('warehouses')->insertGetId([
            'name' => 'Gudang Utama',
            'code' => 'WH-001',
            'address' => 'Pusat',
            'is_default' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('stocks', function (Blueprint $table) use ($defaultWarehouseId) {
            $table->foreignId('warehouse_id')->after('product_id')->default($defaultWarehouseId)->constrained('warehouses')->onDelete('cascade');

            // Remove the old unique constraint on product_id
            $table->dropUnique(['product_id']);

            // Add new composite unique constraint
            $table->unique(['product_id', 'warehouse_id']);
        });

        Schema::table('stock_movements', function (Blueprint $table) use ($defaultWarehouseId) {
            $table->foreignId('warehouse_id')->after('product_id')->default($defaultWarehouseId)->constrained('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });

        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique(['product_id', 'warehouse_id']);
            $table->unique('product_id');
            $table->dropForeign(['warehouse_id']);
            $table->dropColumn('warehouse_id');
        });
    }
};
