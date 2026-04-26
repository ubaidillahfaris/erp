<?php

namespace Tests\Feature\Sprint1;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_journal_entry_cannot_be_updated()
    {
        $entry = JournalEntry::factory()->create(['description' => 'Original Description']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Journal entries are immutable');

        $entry->update(['description' => 'New Description']);
    }

    /** @test */
    public function test_journal_entry_cannot_be_deleted()
    {
        $entry = JournalEntry::factory()->create();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Journal entries are immutable');

        $entry->delete();
    }

    /** @test */
    public function test_journal_item_cannot_be_updated()
    {
        $entry = JournalEntry::factory()->create();
        $account = Account::factory()->create();
        $item = JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Journal items are immutable');

        $item->update(['debit' => 2000]);
    }

    /** @test */
    public function test_journal_item_cannot_be_deleted()
    {
        $entry = JournalEntry::factory()->create();
        $account = Account::factory()->create();
        $item = JournalItem::create([
            'journal_entry_id' => $entry->id,
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Journal items are immutable');

        $item->delete();
    }
}
