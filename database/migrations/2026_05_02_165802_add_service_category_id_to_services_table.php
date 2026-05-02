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
        Schema::table('services', function (Blueprint $table) {
            $table->foreignId('service_category_id')->nullable()->after('description')->constrained('service_categories')->onDelete('set null');
            $table->string('service_category')->nullable()->change();
        });

        // Data Migration: Convert existing string categories to model-based categories
        $services = DB::table('services')->whereNotNull('service_category')->get();

        foreach ($services as $service) {
            $category = DB::table('service_categories')->updateOrInsert(
                [
                    'company_id' => $service->company_id,
                    'name' => $service->service_category,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $categoryId = DB::table('service_categories')
                ->where('company_id', $service->company_id)
                ->where('name', $service->service_category)
                ->value('id');

            DB::table('services')->where('id', $service->id)->update([
                'service_category_id' => $categoryId,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign(['service_category_id']);
            $table->dropColumn('service_category_id');
        });
    }
};
