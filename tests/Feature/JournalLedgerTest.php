<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\Production;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JournalLedgerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        // Ensure permission exists for middleware check
        Permission::findOrCreate('view reports');
        $this->user->givePermissionTo('view reports');
    }

    public function test_unauthorized_user_cannot_view_journal_ledger(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->get(route('accounting.journal.index'));

        $response->assertStatus(403);
    }

    public function test_management_can_view_journal_ledger_with_totals(): void
    {
        $sale = Sale::create([
            'invoice_number' => 'INV-001',
            'tanggal' => now(),
            'total_amount' => 100000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        $entry = JournalEntry::create([
            'ref_number' => 'INV-001',
            'tanggal' => now(),
            'description' => 'Penjualan INV-001',
            'journalable_type' => 'App\Models\Sale',
            'journalable_id' => $sale->id,
            'created_by' => $this->user->id,
        ]);

        $entry->items()->create([
            'account_id' => Account::factory()->create(['code' => '1101'])->id,
            'debit' => 100000,
            'credit' => 0,
        ]);
        $entry->items()->create([
            'account_id' => Account::factory()->create(['code' => '4101'])->id,
            'debit' => 0,
            'credit' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.journal.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('accounting/Journal')
            ->has('journals.data', 1)
            ->where('journals.data.0.items_sum_debit', 100000)
            ->where('journals.data.0.items_sum_credit', 100000)
        );
    }

    public function test_journal_ledger_filters_by_date_range(): void
    {
        // Yesterday's entry
        JournalEntry::create([
            'ref_number' => 'OLD-1',
            'tanggal' => now()->subDay(),
            'description' => 'Old Entry',
        ]);

        // Today's entry
        JournalEntry::create([
            'ref_number' => 'NEW-1',
            'tanggal' => now(),
            'description' => 'New Entry',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.journal.index', [
                'date_start' => now()->format('Y-m-d'),
                'date_end' => now()->format('Y-m-d'),
            ]));

        $response->assertInertia(fn ($page) => $page
            ->has('journals.data', 1)
            ->where('journals.data.0.ref_number', 'NEW-1')
        );
    }

    public function test_journal_ledger_filters_by_type(): void
    {
        // Sale entry
        JournalEntry::create([
            'ref_number' => 'SALE-1',
            'tanggal' => now(),
            'description' => 'Sale',
            'journalable_type' => 'App\Models\Sale',
        ]);

        // Production entry
        JournalEntry::create([
            'ref_number' => 'PRD-1',
            'tanggal' => now(),
            'description' => 'Production',
            'journalable_type' => 'App\Models\Production',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('accounting.journal.index', ['type' => 'SALE']));

        $response->assertInertia(fn ($page) => $page
            ->has('journals.data', 1)
            ->where('journals.data.0.ref_number', 'SALE-1')
        );

        $responsePrd = $this->actingAs($this->user)
            ->get(route('accounting.journal.index', ['type' => 'PRD']));

        $responsePrd->assertInertia(fn ($page) => $page
            ->has('journals.data', 1)
            ->where('journals.data.0.ref_number', 'PRD-1')
        );
    }
}
