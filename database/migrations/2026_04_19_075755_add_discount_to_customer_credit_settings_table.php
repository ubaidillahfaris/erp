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
        Schema::table('customer_credit_settings', function (Blueprint $table) {
            $table->decimal('global_discount', 5, 2)->default(0)->nullable()->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_credit_settings', function (Blueprint $table) {
            $table->dropColumn('global_discount');
        });
    }
};
