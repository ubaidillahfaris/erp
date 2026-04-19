<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\JournalEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Permission::findOrCreate('view reports');
        $this->user->givePermissionTo('view reports');
    }

    public function test_can_create_account(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'code' => '1101',
                'name' => 'Kas Utama',
                'type' => 'asset',
                'balance_type' => 'debit',
                'is_active' => true,
            ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'code' => '1101',
            'name' => 'Kas Utama',
        ]);
    }

    public function test_cannot_create_duplicate_code(): void
    {
        Account::factory()->create(['code' => '1101']);

        $response = $this->actingAs($this->user)
            ->post(route('accounts.store'), [
                'code' => '1101',
                'name' => 'Duplicate Account',
                'type' => 'asset',
                'balance_type' => 'debit',
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_can_update_account_name_when_has_journal_items(): void
    {
        $account = Account::factory()->create(['code' => '1101', 'name' => 'Old Name']);
        
        // Create journal entry to trigger lock
        $entry = JournalEntry::create(['ref_number' => 'TEST-1', 'tanggal' => now(), 'description' => 'Test']);
        $entry->items()->create([
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accounts.update', $account), [
                'code' => '1101', // Unchanged
                'name' => 'New Premium Name',
                'type' => 'asset', // Unchanged
                'balance_type' => 'debit', // Unchanged
                'is_active' => true,
            ]);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'name' => 'New Premium Name',
        ]);
    }

    public function test_cannot_update_account_code_when_has_journal_items(): void
    {
        $account = Account::factory()->create(['code' => '1101', 'name' => 'Old Name', 'type' => 'asset', 'balance_type' => 'debit']);
        
        // Create journal entry to trigger lock
        $entry = JournalEntry::create(['ref_number' => 'TEST-1', 'tanggal' => now(), 'description' => 'Test']);
        $entry->items()->create([
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('accounts.update', $account), [
                'code' => '1102', // CHANGED! Should fail
                'name' => 'New Name',
                'type' => 'asset',
                'balance_type' => 'debit',
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('code');
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'code' => '1101', // Verified unchanged
        ]);
    }

    public function test_cannot_delete_account_with_journal_items(): void
    {
        $account = Account::factory()->create();
        
        // Create journal entry to trigger block
        $entry = JournalEntry::create(['ref_number' => 'TEST-1', 'tanggal' => now(), 'description' => 'Test']);
        $entry->items()->create([
            'account_id' => $account->id,
            'debit' => 1000,
            'credit' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('accounts.destroy', $account));

        $response->assertStatus(422);
        $this->assertDatabaseHas('accounts', ['id' => $account->id]);
    }

    public function test_deactivate_instead_of_delete_when_no_journal_items(): void
    {
        $account = Account::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)
            ->delete(route('accounts.destroy', $account));

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
        
        // Verify it wasn't deleted
        $this->assertTrue(Account::where('id', $account->id)->exists());
    }

    public function test_can_force_delete_when_no_journal_items(): void
    {
        $account = Account::factory()->create();

        $response = $this->actingAs($this->user)
            ->delete(route('accounts.destroy', $account), ['force' => 'true']);

        $response->assertRedirect(route('accounts.index'));
        $this->assertDatabaseMissing('accounts', ['id' => $account->id]);
    }
}
