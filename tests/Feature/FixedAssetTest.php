<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Models\PeriodLock;
use App\Models\User;
use App\Services\DepreciationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FixedAssetTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Account $assetAcc;

    private Account $accumDeprAcc;

    private Account $expenseAcc;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create();

        // Setup permissions
        $this->seed(RoleAndPermissionSeeder::class);
        $this->admin->assignRole('superadmin');

        $this->actingAs($this->admin);

        // Setup Accounts
        $this->assetAcc = Account::create([
            'code' => '1401',
            'name' => 'Equipment',
            'type' => 'asset',
            'balance_type' => 'debit',
        ]);

        $this->accumDeprAcc = Account::create([
            'code' => '1499',
            'name' => 'Accumulated Depr',
            'type' => 'asset',
            'balance_type' => 'credit',
        ]);

        $this->expenseAcc = Account::create([
            'code' => '6301',
            'name' => 'Depr Expense',
            'type' => 'expense',
            'balance_type' => 'debit',
        ]);
    }

    public function test_asset_creation_generates_correct_schedule(): void
    {
        $cost = 12000000; // 120,000.00 in cents? No, request says "acquisition_cost (integer cents)".
        // 12,000,000 cents = 120,000.00
        $salvage = 0;
        $life = 12; // 1 year

        $response = $this->post('/fixed-assets', [
            'name' => 'Test Asset',
            'category' => 'Equipment',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => $cost,
            'useful_life_months' => $life,
            'salvage_value' => $salvage,
            'asset_account_id' => $this->assetAcc->id,
            'depreciation_account_id' => $this->accumDeprAcc->id,
            'expense_account_id' => $this->expenseAcc->id,
        ]);

        $response->assertRedirect();

        $asset = FixedAsset::first();
        $this->assertNotNull($asset);
        $this->assertCount(12, $asset->schedules);

        // Each month should be 1,000,000 cents
        $firstSchedule = $asset->schedules()->orderBy('id')->first();
        $this->assertEquals(1000000, $firstSchedule->depreciation_amount);
        $this->assertEquals(11000000, $firstSchedule->book_value_after);

        // Final month should be 0 book value
        $this->assertEquals(0, $asset->schedules()->latest('id')->first()->book_value_after);
    }

    public function test_salvage_value_is_respected(): void
    {
        $cost = 10000000; // 100k
        $salvage = 1000000; // 10k
        $life = 3;

        $asset = FixedAsset::create([
            'name' => 'Asset with Salvage',
            'category' => 'Test',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => $cost,
            'useful_life_months' => $life,
            'salvage_value' => $salvage,
            'current_book_value' => $cost,
            'asset_account_id' => $this->assetAcc->id,
            'depreciation_account_id' => $this->accumDeprAcc->id,
            'expense_account_id' => $this->expenseAcc->id,
        ]);

        $service = app(DepreciationService::class);
        $service->generateSchedule($asset);

        $this->assertEquals($salvage, $asset->schedules()->orderBy('id', 'desc')->first()->book_value_after);
    }

    public function test_post_period_creates_journal_entries(): void
    {
        $cost = 12000000;
        $asset = FixedAsset::create([
            'name' => 'Depreciating Asset',
            'category' => 'Test',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => $cost,
            'useful_life_months' => 12,
            'salvage_value' => 0,
            'current_book_value' => $cost,
            'asset_account_id' => $this->assetAcc->id,
            'depreciation_account_id' => $this->accumDeprAcc->id,
            'expense_account_id' => $this->expenseAcc->id,
        ]);

        $service = app(DepreciationService::class);
        $service->generateSchedule($asset);

        // Schedule for month 1 is Feb 2026 (add 1 month from acquisition)
        // Wait, check my service logic: $scheduleDate = $currentDate->copy()->addMonths($i);
        // If acquisition is Jan, i=1 -> Feb.
        $month = 2;
        $year = 2026;

        $count = $service->postPeriod($month, $year);
        $this->assertEquals(1, $count);

        $schedule = $asset->schedules()->where('period_month', $month)->first();
        $this->assertEquals('posted', $schedule->status);
        $this->assertNotNull($schedule->journal_entry_id);

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $schedule->journal_entry_id,
            'account_id' => $this->expenseAcc->id,
            'debit' => 1000000,
        ]);

        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $schedule->journal_entry_id,
            'account_id' => $this->accumDeprAcc->id,
            'credit' => 1000000,
        ]);

        $asset->refresh();
        $this->assertEquals(11000000, $asset->current_book_value);
    }

    public function test_period_lock_is_respected(): void
    {
        PeriodLock::create([
            'period_month' => 2,
            'period_year' => 2026,
            'is_locked' => true,
        ]);

        $asset = FixedAsset::create([
            'name' => 'Locked Asset',
            'category' => 'Test',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 12000000,
            'useful_life_months' => 12,
            'salvage_value' => 0,
            'current_book_value' => 12000000,
            'asset_account_id' => $this->assetAcc->id,
            'depreciation_account_id' => $this->accumDeprAcc->id,
            'expense_account_id' => $this->expenseAcc->id,
        ]);

        $service = app(DepreciationService::class);
        $service->generateSchedule($asset);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Period 2/2026 is locked.');

        $service->postPeriod(2, 2026);
    }

    public function test_dispose_stops_future_depreciation(): void
    {
        $asset = FixedAsset::create([
            'name' => 'To be disposed',
            'category' => 'Test',
            'acquisition_date' => '2026-01-01',
            'acquisition_cost' => 12000000,
            'useful_life_months' => 12,
            'salvage_value' => 0,
            'current_book_value' => 12000000,
            'asset_account_id' => $this->assetAcc->id,
            'depreciation_account_id' => $this->accumDeprAcc->id,
            'expense_account_id' => $this->expenseAcc->id,
        ]);

        $service = app(DepreciationService::class);
        $service->generateSchedule($asset);

        $this->post("/fixed-assets/{$asset->id}/dispose", [
            'disposal_date' => '2026-03-01',
            'notes' => 'Damaged',
        ]);

        $asset->refresh();
        $this->assertEquals('disposed', $asset->status);
        $this->assertCount(0, $asset->schedules()->where('status', 'scheduled')->get());
    }
}
