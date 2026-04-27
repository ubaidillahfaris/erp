<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\CreditNote;
use App\Models\Customer;
use App\Models\CustomerCreditSetting;
use App\Models\CustomerStatus;
use App\Models\CustomerType;
use App\Models\JournalEntry;
use App\Models\Payable;
use App\Models\Price;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNoteTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $warehouse;

    protected $product;

    protected $unit;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Accounts
        Account::create(['code' => '1101', 'name' => 'Cash', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1102', 'name' => 'Receivable', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '1302', 'name' => 'Finished Goods', 'type' => 'asset', 'balance_type' => 'debit']);
        Account::create(['code' => '4101', 'name' => 'Sales', 'type' => 'income', 'balance_type' => 'credit']);
        Account::create(['code' => '5101', 'name' => 'COGS', 'type' => 'expense', 'balance_type' => 'debit']);

        $this->user = User::factory()->superadmin()->create();
        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'code' => 'WH001',
            'is_default' => true,
            'is_active' => true,
        ]);
        $this->unit = Unit::create(['name' => 'pcs', 'symbol' => 'pcs']);
        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'PRD-001',
            'unit_id' => $this->unit->id,
            'is_active' => true,
        ]);

        Price::create([
            'product_id' => $this->product->id,
            'unit_id' => $this->unit->id,
            'purchase_price' => 1000,
            'retail_price' => 2000,
            'is_current' => true,
        ]);

        Stock::create([
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'balance' => 100,
            'last_unit_id' => $this->unit->id,
        ]);
    }

    public function test_can_create_credit_note_draft()
    {
        $sale = $this->createSale();

        $this->actingAs($this->user);

        $response = $this->post(route('credit-notes.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Defective product',
            'items' => [
                [
                    'sale_item_id' => $sale->items->first()->id,
                    'quantity_returned' => 2,
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('credit_notes', [
            'sale_id' => $sale->id,
            'status' => 'draft',
            'total_amount' => 4000, // 2 * 2000
        ]);

        $this->assertDatabaseHas('credit_note_items', [
            'product_id' => $this->product->id,
            'quantity_returned' => 2,
            'subtotal' => 4000,
        ]);
    }

    public function test_can_post_credit_note_and_reverse_everything()
    {
        $sale = $this->createSale();
        $cn = $this->createCreditNote($sale, 3);

        $this->actingAs($this->user);

        // Current stock after sale: 100 - 5 = 95
        $response = $this->post(route('credit-notes.post', $cn->id));
        $response->assertRedirect();

        // 1. Assert Status
        $this->assertEquals('posted', $cn->fresh()->status);

        // 2. Assert Stock: 95 + 3 = 98
        $stock = Stock::where('product_id', $this->product->id)->first();
        $this->assertEquals(98, (float) $stock->balance);

        // 3. Assert Journal Entries
        // Revenue Reversal: Dr. 4101 (Sales), Cr. 1101 (Cash)
        $revJournal = JournalEntry::where('journalable_type', CreditNote::class)
            ->where('description', 'LIKE', '%Pembalikan Pendapatan%')
            ->first();
        $this->assertNotNull($revJournal);

        // Rev amount: 3 * 2000 = 6000
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $revJournal->id,
            'account_id' => Account::where('code', '4101')->first()->id,
            'debit' => 600000,
        ]);

        // COGS Reversal: Dr. 1302 (Inv), Cr. 5101 (COGS)
        $cogsJournal = JournalEntry::where('journalable_type', CreditNote::class)
            ->where('description', 'LIKE', '%Pembalikan HPP%')
            ->first();
        $this->assertNotNull($cogsJournal);

        // COGS amount: 3 * 1000 = 3000
        $this->assertDatabaseHas('journal_items', [
            'journal_entry_id' => $cogsJournal->id,
            'account_id' => Account::where('code', '1302')->first()->id,
            'debit' => 300000,
        ]);
    }

    public function test_cannot_return_more_than_original_quantity()
    {
        $sale = $this->createSale(5);
        $this->actingAs($this->user);

        // Attempt to return 6
        $response = $this->post(route('credit-notes.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Too much',
            'items' => [
                [
                    'sale_item_id' => $sale->items->first()->id,
                    'quantity_returned' => 6,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_cumulative_return_validation()
    {
        $sale = $this->createSale(10);
        $this->actingAs($this->user);

        // First return: 6 (posted)
        $cn1 = $this->createCreditNote($sale, 6);
        $this->post(route('credit-notes.post', $cn1->id));

        // Second return: 5 (should fail, 6 + 5 > 10)
        $response = $this->post(route('credit-notes.store'), [
            'sale_id' => $sale->id,
            'reason' => 'Still too much',
            'items' => [
                [
                    'sale_item_id' => $sale->items->first()->id,
                    'quantity_returned' => 5,
                ],
            ],
        ]);

        $response->assertSessionHasErrors('error');
    }

    public function test_reduces_payable_for_credit_sales()
    {
        // 1. Setup Customer with Credit
        $type = CustomerType::create(['name' => 'Wholesale']);
        $status = CustomerStatus::create(['name' => 'Active']);

        $customer = Customer::create([
            'name' => 'Credit Customer',
            'customer_type_id' => $type->id,
            'customer_status_id' => $status->id,
        ]);
        CustomerCreditSetting::create([
            'customer_id' => $customer->id,
            'allow_credit' => true,
            'credit_limit' => 1000000,
        ]);

        // 2. Create Credit Sale
        $sale = $this->createSale(10, 'credit', $customer);

        $payable = Payable::where('reference_type', 'sale')->where('reference_id', $sale->id)->first();
        $this->assertNotNull($payable);
        $this->assertEquals(20000, (float) $payable->remaining_amount); // 10 * 2000

        // 3. Create Return
        $cn = $this->createCreditNote($sale, 4); // 4 * 2000 = 8000
        $this->actingAs($this->user);
        $this->post(route('credit-notes.post', $cn->id));

        // 4. Assert Payable reduced
        $this->assertEquals(12000, (float) $payable->fresh()->remaining_amount);
    }

    protected function createSale($qty = 5, $paymentMethod = 'cash', $customer = null)
    {
        $this->actingAs($this->user);
        $payload = [
            'date' => now()->format('Y-m-d'),
            'warehouse_id' => $this->warehouse->id,
            'payment_method' => $paymentMethod,
            'customer_id' => $customer?->id,
            'received_amount' => $qty * 2000,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'unit_id' => $this->unit->id,
                    'qty' => $qty,
                    'price' => 2000,
                    'cost' => 1000,
                ],
            ],
        ];

        if ($customer) {
            $payload['customer_id'] = $customer->id;
        }

        $this->post(route('pos.store'), $payload);

        return Sale::latest()->first();
    }

    protected function createCreditNote($sale, $qty)
    {
        $cn = CreditNote::create([
            'credit_note_number' => 'CN-'.uniqid(),
            'sale_id' => $sale->id,
            'status' => 'draft',
            'reason' => 'Test return',
            'total_amount' => $qty * 2000,
            'created_by' => $this->user->id,
        ]);

        $cn->items()->create([
            'sale_item_id' => $sale->items->first()->id,
            'product_id' => $this->product->id,
            'quantity_returned' => $qty,
            'unit_price' => 2000,
            'subtotal' => $qty * 2000,
        ]);

        return $cn;
    }

    public function test_can_view_credit_notes_index()
    {
        $sale = $this->createSale();
        $this->createCreditNote($sale, 2);

        $this->actingAs($this->user);
        $response = $this->get(route('credit-notes.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CreditNotes/Index')
            ->has('creditNotes.data', 1)
        );
    }

    public function test_can_view_credit_note_show()
    {
        $sale = $this->createSale();
        $cn = $this->createCreditNote($sale, 2);

        $this->actingAs($this->user);
        $response = $this->get(route('credit-notes.show', $cn->id));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CreditNotes/Show')
            ->has('creditNote')
        );
    }

    public function test_can_view_credit_note_create_general()
    {
        $this->actingAs($this->user);
        $response = $this->get(route('credit-notes.create-general'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CreditNotes/Create')
        );
    }

    public function test_can_get_sale_details()
    {
        $sale = $this->createSale();

        $this->actingAs($this->user);
        $response = $this->getJson(route('credit-notes.sale-details', $sale->id));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'invoice_number',
            'items' => [
                '*' => [
                    'id',
                    'product',
                    'unit',
                    'qty',
                    'price',
                    'returnable_qty',
                ]
            ]
        ]);
    }
}
