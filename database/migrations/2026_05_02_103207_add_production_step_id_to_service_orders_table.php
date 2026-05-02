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
        Schema::table('service_orders', function (Blueprint $table) {
            $table->foreignId('production_step_id')->nullable()->after('service_id')->constrained('production_steps')->nullOnDelete();
            $table->dropColumn('current_status_code');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders', function (Blueprint $table) {
            $table->string('current_status_code')->nullable()->after('service_id');
            $table->dropConstrainedForeignId('production_step_id');
        });
    }
};
