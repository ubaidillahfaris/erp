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
        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->foreignId('category_id')->after('customer_id')->nullable()->constrained('categories')->nullOnDelete();
        });

        $discounts = DB::table('customer_category_discounts')->select('id', 'kategori')->get();

        foreach ($discounts as $discount) {
            if ($discount->kategori) {
                $category = DB::table('categories')->where('name', $discount->kategori)->first();
                if ($category) {
                    DB::table('customer_category_discounts')->where('id', $discount->id)->update(['category_id' => $category->id]);
                }
            }
        }

        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->string('kategori')->after('customer_id')->nullable();
        });

        $discounts = DB::table('customer_category_discounts')->select('id', 'category_id')->get();

        foreach ($discounts as $discount) {
            if ($discount->category_id) {
                $category = DB::table('categories')->where('id', $discount->category_id)->first();
                if ($category) {
                    DB::table('customer_category_discounts')->where('id', $discount->id)->update(['kategori' => $category->name]);
                }
            }
        }

        Schema::table('customer_category_discounts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
