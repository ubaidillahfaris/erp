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
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->timestamp('storno_at')->nullable()->after('status');
            $table->string('storno_reason')->nullable()->after('storno_at');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });

        Schema::table('productions', function (Blueprint $table) {
            $table->string('status')->default('draft')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropColumn(['storno_at', 'storno_reason']);
        });

        // We don't necessarily want to revert to enum if we already have 'storno' values
        // but for symmetry:
        /*
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['draft', 'finalized'])->default('draft')->change();
        });
        */
    }
};
