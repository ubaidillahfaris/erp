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
        // 1. Add category_id to produks
        Schema::table('produks', function (Blueprint $table) {
            $table->foreignId('category_id')->after('nama')->nullable()->constrained('categories')->nullOnDelete();
        });

        // 2. Data Migration
        $produks = DB::table('produks')->select('id', 'kategori')->get();

        foreach ($produks as $produk) {
            if ($produk->kategori) {
                $category = DB::table('categories')->where('name', $produk->kategori)->first();

                if (! $category) {
                    $id = DB::table('categories')->insertGetId([
                        'name' => $produk->kategori,
                        'slug' => str($produk->kategori)->slug(),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $categoryId = $id;
                } else {
                    $categoryId = $category->id;
                }

                DB::table('produks')->where('id', $produk->id)->update(['category_id' => $categoryId]);
            }
        }

        // 3. Drop kategori column
        Schema::table('produks', function (Blueprint $table) {
            $table->dropColumn('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produks', function (Blueprint $table) {
            $table->string('kategori')->after('nama')->nullable();
        });

        $produks = DB::table('produks')->select('id', 'category_id')->get();

        foreach ($produks as $produk) {
            if ($produk->category_id) {
                $category = DB::table('categories')->where('id', $produk->category_id)->first();
                if ($category) {
                    DB::table('produks')->where('id', $produk->id)->update(['kategori' => $category->name]);
                }
            }
        }

        Schema::table('produks', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
    }
};
