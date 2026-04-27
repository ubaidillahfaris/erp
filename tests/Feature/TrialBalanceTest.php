<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\User;
use App\Services\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class TrialBalanceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private $journalService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Permission::findOrCreate('view reports');
        $this->user->givePermissionTo('view reports');

        $this->journalService = app(JournalService::class);
        Cache::forget('trial_balance_current');
    }

    public function test_trial_balance_loads_all_active_accounts(): void
    {
        Account::factory()->create(['code' => '1101', 'is_active' => true]);
        Account::factory()->create(['code' => '1102', 'is_active' => true]);
        Account::factory()->create(['code' => 'HIDDEN', 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.trial-balance.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('accounting/TrialBalance')
            ->has('accounts', 2)
            ->where('accounts.0.code', '1101')
            ->where('accounts.1.code', '1102')
        );
    }

    public function test_trial_balance_is_balanced_when_entries_correct(): void
    {
        $acc1 = Account::factory()->create(['balance_type' => 'debit']);
        $acc2 = Account::factory()->create(['balance_type' => 'credit']);

        $data = new JournalEntryData(
            description: 'Balanced Entry',
            items: [
                new JournalItemData(account_id: $acc1->id, type: 'debit', amount: 100000),
                new JournalItemData(account_id: $acc2->id, type: 'credit', amount: 100000),
            ],
            date: now()
        );

        $this->journalService->record($data);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.trial-balance.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('is_balanced', true)
            ->where('totals.debit', 100000)
            ->where('totals.credit', 100000)
        );
    }

    public function test_trial_balance_flags_out_of_balance(): void
    {
        $acc1 = Account::factory()->create(['balance_type' => 'debit']);
        $acc2 = Account::factory()->create(['balance_type' => 'credit']);

        // Force UNBALANCED in DB (bypassing service to create faulty state)
        $entry = JournalEntry::create(['ref_number' => 'FAULTY', 'date' => now(), 'description' => 'Unbalanced']);
        JournalItem::create(['journal_entry_id' => $entry->id, 'account_id' => $acc1->id, 'debit' => 100000, 'credit' => 0]);
        JournalItem::create(['journal_entry_id' => $entry->id, 'account_id' => $acc2->id, 'debit' => 0, 'credit' => 50000]); // WRONG CREDIT

        Cache::forget('trial_balance_current');

        $response = $this->actingAs($this->user)
            ->get(route('accounting.trial-balance.index'));

        $response->assertInertia(fn ($page) => $page
            ->where('is_balanced', false)
            ->where('totals.debit', 100000)
            ->where('totals.credit', 50000)
        );
    }

    public function test_cache_is_invalidated_after_new_journal_entry(): void
    {
        $acc1 = Account::factory()->create();
        $acc2 = Account::factory()->create();

        // 1. Initial Load to prime cache
        $this->actingAs($this->user)->get(route('accounting.trial-balance.index'));
        $this->assertTrue(Cache::has('trial_balance_current'));

        // 2. Perform transaction
        $data = new JournalEntryData(
            description: 'Trigger Invalidation',
            items: [
                new JournalItemData(account_id: $acc1->id, type: 'debit', amount: 50000),
                new JournalItemData(account_id: $acc2->id, type: 'credit', amount: 50000),
            ],
            date: now()
        );

        $this->journalService->record($data);

        // 3. Verify Cache FORGOTTEN
        $this->assertFalse(Cache::has('trial_balance_current'), 'Cache did not invalidate after JournalService::record');
    }
}
