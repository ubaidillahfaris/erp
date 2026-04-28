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
        Schema::create('depreciation_schedules', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('fixed_asset_id')->constrained('fixed_assets')->onDelete('cascade');
            $blueprint->integer('period_month');
            $blueprint->integer('period_year');
            $blueprint->bigInteger('depreciation_amount');
            $blueprint->bigInteger('book_value_after');
            $blueprint->foreignId('journal_entry_id')->nullable()->constrained('journal_entries');
            $blueprint->string('status')->default('scheduled'); // scheduled, posted
            $blueprint->timestamps();

            $blueprint->unique(['fixed_asset_id', 'period_month', 'period_year'], 'asset_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_schedules');
    }
};
