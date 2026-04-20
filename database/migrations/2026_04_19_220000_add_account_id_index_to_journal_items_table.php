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
        // Index already exists — skip silently
        if (\DB::connection()->getDriverName() === 'pgsql') {
            if (collect(\DB::select("SELECT indexname FROM pg_indexes WHERE tablename = 'journal_items' AND indexname = 'journal_items_account_id_index'"))->isNotEmpty()) {
                return;
            }
        } else if (\DB::connection()->getDriverName() === 'sqlite') {
            // Check sqlite sqlite_master for index
            $exists = \DB::select("SELECT name FROM sqlite_master WHERE type='index' AND name='journal_items_account_id_index'");
            if (!empty($exists)) return;
        }
        Schema::table('journal_items', function (Blueprint $table) {
            $table->index('account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_items', function (Blueprint $table) {
            $table->dropIndex(['account_id']);
        });
    }
};
