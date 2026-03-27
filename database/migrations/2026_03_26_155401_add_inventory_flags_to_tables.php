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
        Schema::table('produks', function (Blueprint $table) {
            $table->boolean('track_stock')->default(true)->after('type');
        });

        Schema::table('boms', function (Blueprint $table) {
            $table->boolean('auto_deduct_on_sale')->default(false)->after('expected_yield');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->dropColumn('track_stock');
        });

        Schema::table('boms', function (Blueprint $table) {
            $table->dropColumn('auto_deduct_on_sale');
        });
    }
};
