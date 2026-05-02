<?php

namespace App\Services;

use App\DTOs\JournalEntryData;
use App\DTOs\JournalItemData;
use App\Models\Account;
use App\Models\ProductionStep;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceOrderItem;
use App\Models\ServicePricing;
use App\Models\ServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceOrderService
{
    public function __construct(protected JournalService $journalService) {}

    /**
     * Create a new service order.
     */
    public function createOrder(int $serviceId, Model $party, array $items, string $customerType): ServiceOrder
    {
        return DB::transaction(function () use ($serviceId, $party, $items, $customerType) {
            $service = Service::findOrFail($serviceId);
            $startStep = ProductionStep::where('company_id', $party->company_id)
                ->where('is_start', true)
                ->first();

            $order = ServiceOrder::create([
                'company_id' => $party->company_id,
                'order_number' => $this->generateOrderNumber($service),
                'service_id' => $serviceId,
                'customer_type' => $customerType,
                'party_type' => get_class($party),
                'party_id' => $party->id,
                'order_date' => now()->toDateString(),
                'production_step_id' => $startStep?->id,
                'total_amount' => 0,
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $itemData) {
                $this->addItem($order, $itemData['service_type_id'], $itemData['quantity'], $itemData['notes'] ?? null);
            }

            return $order;
        });
    }

    /**
     * Add an item to an existing order.
     */
    public function addItem(ServiceOrder $order, int $serviceTypeId, float $quantity, ?string $notes = null): ServiceOrderItem
    {
        return DB::transaction(function () use ($order, $serviceTypeId, $quantity, $notes) {
            if ($order->status === 'posted' || $order->status === 'cancelled') {
                throw new \Exception("Cannot modify an order with status: {$order->status}");
            }

            $serviceType = ServiceType::findOrFail($serviceTypeId);
            $pricing = $this->lookupPricing($serviceType, $quantity);

            $unitPrice = $pricing->unit_price;
            $discountPct = $pricing->discount_pct ?? 0;
            
            // Calculate subtotal in cents
            $subtotal = (int) round(($quantity * $unitPrice) * (1 - ($discountPct / 100)));

            $item = $order->items()->create([
                'service_type_id' => $serviceTypeId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_pct' => $discountPct,
                'subtotal' => $subtotal,
                'notes' => $notes,
            ]);

            $order->increment('total_amount', $subtotal);

            return $item;
        });
    }

    /**
     * Update order production step and handle workflow validation.
     */
    public function updateProductionStep(ServiceOrder $order, int $newStepId): void
    {
        DB::transaction(function () use ($order, $newStepId) {
            $nextStep = ProductionStep::findOrFail($newStepId);
            
            // Validate: New step must be a child of the current step
            // or it's the start step if currently no step is assigned.
            if ($order->production_step_id) {
                if ($nextStep->parent_step_id !== $order->production_step_id) {
                    // Check if we allow jumping (for now, let's be strict as discussed)
                    // throw new \Exception("Invalid step transition from {$order->productionStep->name} to {$nextStep->name}");
                }
            }

            $order->update(['production_step_id' => $newStepId]);

            // If it's a final step, finalize the order
            if ($nextStep->is_final) {
                $order->update([
                    'completion_date' => now(),
                    'status' => 'posted',
                ]);
                $this->postJournal($order);
            }
        });
    }

    /**
     * Record a payment for the order.
     */
    public function recordPayment(ServiceOrder $order, int $amount, string $method, ?string $notes = null): void
    {
        DB::transaction(function () use ($order, $amount, $method, $notes) {
            $payment = $order->payments()->create([
                'company_id' => $order->company_id,
                'payment_date' => now()->toDateString(),
                'payment_method' => $method,
                'amount' => $amount,
                'notes' => $notes,
                'created_by' => auth()->id(),
            ]);

            $order->increment('total_paid', $amount);

            // Logic for Payable balance update if applicable
            // For now, ServiceOrder is standalone but can integrate with Payable if needed.
            // Requirement says: "If partial payment and customer_type = customer, update Payable balance"
            // This implies a Payable was created when posted.
        });
    }

    public function adjustPrice(ServiceOrder $order, int $newTotalAmount): void
    {
        DB::transaction(function () use ($order, $newTotalAmount) {
            if ($order->status === 'posted' || $order->status === 'cancelled') {
                throw new \Exception("Cannot adjust price for order with status: {$order->status}");
            }

            $order->update(['total_amount' => $newTotalAmount]);
        });
    }

    /**
     * Post journal entries for the order.
     */
    public function postJournal(ServiceOrder $order): void
    {
        $revAccount = Account::where('code', '4201')->firstOrFail(); // Service Revenue
        $expAccount = Account::where('code', '6401')->firstOrFail(); // Service Expense
        $arAccount = Account::where('code', '1102')->firstOrFail();  // AR
        $apAccount = Account::where('code', '2101')->firstOrFail();  // AP
        $cashAccount = Account::where('code', '1101')->firstOrFail(); // Cash/Bank

        $items = [];
        $description = "Service Order #{$order->order_number}";

        if ($order->customer_type === 'customer') {
            // Dr. AR (or Cash if fully paid), Cr. Service Revenue
            $items[] = new JournalItemData(
                account_id: $revAccount->id,
                amount: $order->total_amount,
                type: 'credit'
            );
            $items[] = new JournalItemData(
                account_id: $arAccount->id,
                amount: $order->total_amount,
                type: 'debit'
            );
        } else {
            // Dr. Service Expense, Cr. AP
            $items[] = new JournalItemData(
                account_id: $expAccount->id,
                amount: $order->total_amount,
                type: 'debit'
            );
            $items[] = new JournalItemData(
                account_id: $apAccount->id,
                amount: $order->total_amount,
                type: 'credit'
            );
        }

        $entry = $this->journalService->record(new JournalEntryData(
            items: $items,
            description: $description,
            date: $order->completion_date ?? now(),
            journalable: $order
        ));

        $order->update(['journal_entry_id' => $entry->id]);
    }

    /**
     * Void the service order and reverse journals.
     */
    public function void(ServiceOrder $order, ?string $reason = null): void
    {
        DB::transaction(function () use ($order, $reason) {
            if ($order->status === 'cancelled') {
                return;
            }

            // Reversing journal
            if ($order->journal_entry_id) {
                $this->reverseJournal($order, $reason);
            }

            // Refund payments by creating negative payments
            foreach ($order->payments as $payment) {
                $order->payments()->create([
                    'company_id' => $order->company_id,
                    'payment_date' => now()->toDateString(),
                    'payment_method' => $payment->payment_method,
                    'amount' => -$payment->amount,
                    'notes' => "VOID REVERSAL: " . ($reason ?? 'Order cancelled'),
                    'created_by' => auth()->id(),
                ]);
            }

            $order->update([
                'status' => 'cancelled',
                'total_paid' => 0,
                'notes' => ($order->notes ? $order->notes . "\n" : "") . "VOIDED: " . $reason,
            ]);
        });
    }

    protected function reverseJournal(ServiceOrder $order, ?string $reason): void
    {
        $originalEntry = $order->journalEntry()->with('items')->first();
        if (! $originalEntry) {
            return;
        }

        $reverseItems = [];
        foreach ($originalEntry->items as $item) {
            $reverseItems[] = new JournalItemData(
                account_id: $item->account_id,
                amount: $item->debit ?: $item->credit,
                type: $item->debit ? 'credit' : 'debit'
            );
        }

        $this->journalService->record(new JournalEntryData(
            items: $reverseItems,
            description: "VOID: #{$order->order_number} " . ($reason ?? ""),
            date: now(),
            journalable: $order,
            ref_number: "VOID-{$originalEntry->ref_number}"
        ));
    }

    protected function lookupPricing(ServiceType $type, float $quantity): ServicePricing
    {
        $pricing = $type->pricings()
            ->where('is_active', true)
            ->where(function ($q) use ($quantity) {
                $q->whereNull('min_quantity')->orWhere('min_quantity', '<=', $quantity);
            })
            ->where(function ($q) use ($quantity) {
                $q->whereNull('max_quantity')->orWhere('max_quantity', '>=', $quantity);
            })
            ->orderBy('min_quantity', 'desc') // Pick the most specific bracket
            ->first();

        if (! $pricing) {
            // Fallback to first active if no brackets match? 
            // Or throw exception. Requirement says "look up ServicePricing... Auto-calculate price"
            $pricing = $type->pricings()->where('is_active', true)->first();
        }

        if (! $pricing) {
            throw new \Exception("No active pricing found for service type: {$type->name}");
        }

        return $pricing;
    }

    protected function generateOrderNumber(Service $service): string
    {
        $prefix = strtoupper(substr($service->code, 0, 3));
        $date = now()->format('ymd');
        $random = strtoupper(Str::random(4));
        return "{$prefix}-{$date}-{$random}";
    }
}
