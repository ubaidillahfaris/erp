<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Exceptions\BalanceMismatchException;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalServiceTest extends TestCase
{
    use RefreshDatabase;

    private JournalService $service;
    private Account $cash;
    private Account $revenue;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new JournalService();

        $this->cash = Account::create([
            'code' => '1101',
            'name' => 'Cash',
            'type' => 'asset',
            'balance_type' => 'debit',
        ]);

        $this->revenue = Account::create([
            'code' => '4101',
            'name' => 'Revenue',
            'type' => 'income',
            'balance_type' => 'credit',
        ]);
    }

    public function test_balanced_entry_returns_instance_and_stores_cents(): void
    {
        // 100050 cents = 1000.50
        $data = new JournalEntryData(
            items: [
                new JournalItemData(account_id: $this->cash->id, amount: 100050, type: 'debit'),
                new JournalItemData(account_id: $this->revenue->id, amount: 100050, type: 'credit'),
            ],
            description: 'Test Sales'
        );

        $entry = $this->service->record($data);

        $this->assertInstanceOf(JournalEntry::class, $entry);
        $this->assertDatabaseHas('journal_entries', ['id' => $entry->id]);
        
        // Verify cents storage
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->cash->id,
            'debit' => 100050,
            'credit' => 0,
        ]);
        
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $entry->id,
            'account_id' => $this->revenue->id,
            'debit' => 0,
            'credit' => 100050,
        ]);
    }

    public function test_unbalanced_entry_throws_exception(): void
    {
        $this->expectException(BalanceMismatchException::class);

        $data = new JournalEntryData(
            items: [
                new JournalItemData(account_id: $this->cash->id, amount: 100000, type: 'debit'),
                new JournalItemData(account_id: $this->revenue->id, amount: 99900, type: 'credit'),
            ]
        );

        $this->service->record($data);
    }

    public function test_database_failure_rolls_back_everything(): void
    {
        $initialCount = JournalEntry::count();

        try {
            DB::transaction(function () {
                $data = new JournalEntryData(
                    items: [
                        new JournalItemData(account_id: $this->cash->id, amount: 10000, type: 'debit'),
                        new JournalItemData(account_id: $this->revenue->id, amount: 10000, type: 'credit'),
                    ]
                );
                
                $this->service->record($data);
                
                // Simulate failure after service call
                throw new \RuntimeException('Manual crash');
            });
        } catch (\RuntimeException $e) {
            // Expected
        }

        $this->assertEquals($initialCount, JournalEntry::count());
        $this->assertEquals(0, JournalItem::count());
    }

    public function test_duplicate_ref_number_fails(): void
    {
        $data = new JournalEntryData(
            items: [
                new JournalItemData(account_id: $this->cash->id, amount: 10000, type: 'debit'),
                new JournalItemData(account_id: $this->revenue->id, amount: 10000, type: 'credit'),
            ],
            ref_number: 'UNIQUE-REF-1'
        );

        $this->service->record($data);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->service->record($data);
    }
}
