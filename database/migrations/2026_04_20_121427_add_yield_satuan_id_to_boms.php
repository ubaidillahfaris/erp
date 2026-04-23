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
            $table->foreignId('yield_satuan_id')->nullable()->after('expected_yield')->constrained('satuans');
        });

        // Backfill yield_satuan_id from produk's satuan_id
        DB::statement('
            UPDATE boms 
            SET yield_satuan_id = (
                SELECT satuan_id FROM produks 
                WHERE produks.id = boms.produk_id
            )
        ');

        Schema::table('boms', function (Blueprint $table) {
            $table->unsignedBigInteger('yield_satuan_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boms', function (Blueprint $table) {
            $table->dropForeign(['yield_satuan_id']);
            $table->dropColumn('yield_satuan_id');
        });
    }
};
