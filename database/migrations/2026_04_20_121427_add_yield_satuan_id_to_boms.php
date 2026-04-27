<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->foreignId('yield_unit_id')->nullable()->after('expected_yield')->constrained('units');
        });

        // Backfill yield_unit_id from product's unit_id
        DB::statement('
            UPDATE boms 
            SET yield_unit_id = (
                SELECT unit_id FROM products 
                WHERE products.id = boms.product_id
            )
        ');

        Schema::table('boms', function (Blueprint $table) {
            $table->unsignedBigInteger('yield_unit_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropForeign(['yield_unit_id']);
            $table->dropColumn('yield_unit_id');
        });
    }
};
