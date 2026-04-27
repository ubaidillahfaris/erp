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
        Schema::table('restocks', function (Blueprint $table) {
            $table->string('status_pembayaran')->default('lunas')->after('notes');
            $table->decimal('total_bayar', 15, 2)->default(0)->after('status_pembayaran');
            $table->json('biaya_tambahan')->nullable()->after('total_bayar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restocks', function (Blueprint $table) {
            $table->dropColumn(['status_pembayaran', 'total_bayar', 'biaya_tambahan']);
        });
    }
};
