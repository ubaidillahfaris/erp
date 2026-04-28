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
        Schema::create('fixed_assets', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('asset_code')->unique();
            $blueprint->string('name');
            $blueprint->text('description')->nullable();
            $blueprint->string('category');
            $blueprint->date('acquisition_date');
            $blueprint->bigInteger('acquisition_cost');
            $blueprint->integer('useful_life_months');
            $blueprint->bigInteger('salvage_value')->default(0);
            $blueprint->bigInteger('current_book_value');
            $blueprint->string('status')->default('active'); // active, disposed, fully_depreciated

            $blueprint->foreignId('asset_account_id')->constrained('accounts');
            $blueprint->foreignId('depreciation_account_id')->constrained('accounts');
            $blueprint->foreignId('expense_account_id')->constrained('accounts');

            $blueprint->foreignId('created_by')->constrained('users');
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
