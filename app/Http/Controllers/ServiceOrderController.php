<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductionStep;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\Vendor;
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    public function __construct(protected ServiceOrderService $serviceOrderService) {}

    /**
     * Display a listing of service orders.
     */
    public function index(Request $request): Response
    {
        $view = $request->query('view', 'kanban');

        $query = ServiceOrder::with(['service', 'party', 'productionStep'])
            ->when($request->search, function ($q, $search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('party', function ($qp) use ($search) {
                        $qp->where('name', 'like', "%{$search}%");
                    });
            })
            ->when($request->service_id, fn ($q, $id) => $q->where('service_id', $id))
            ->when($request->production_step_id, fn ($q, $id) => $q->where('production_step_id', $id))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date_start, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_end, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->orderBy('created_at', 'desc');

        // Default filters for Kanban view: exclude cancelled orders, but show posted (completed) ones
        // so they appear in the final column.
        if ($view === 'kanban' && ! $request->status && ! $request->search) {
            $query->where('status', '!=', 'cancelled');
        }

        return Inertia::render('service-orders/Index', [
            'orders' => $query->paginate($view === 'kanban' ? 50 : 10)->withQueryString(),
            'services' => Service::all(),
            'steps' => ProductionStep::orderBy('sequence_order')->get(),
            'filters' => $request->only(['search', 'status', 'date_start', 'date_end', 'view', 'service_id', 'production_step_id']),
        ]);
    }

    /**
     * Show the form for creating a new service order.
     */
    public function create(): Response
    {
        return Inertia::render('service-orders/Pos', [
            'services' => Service::with('serviceTypes.pricings')->where('is_active', true)->get(),
        ]);
    }

    /**
     * Store a newly created service order.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'service_id' => 'required|exists:services,id',
            'customer_type' => 'required|in:customer,vendor',
            'party_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.service_type_id' => 'required|exists:service_types,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.notes' => 'nullable|string',
        ]);

        $partyClass = $validated['customer_type'] === 'customer' ? Customer::class : Vendor::class;
        $party = $partyClass::findOrFail($validated['party_id']);

        $order = $this->serviceOrderService->createOrder(
            $validated['service_id'],
            $party,
            $validated['items'],
            $validated['customer_type']
        );

        return redirect()->route('service-orders.show', $order)
            ->with('success', 'Order created successfully.');
    }

    /**
     * Display the specified service order.
     */
    public function show(ServiceOrder $serviceOrder): Response
    {
        $serviceOrder->load(['party', 'service', 'items.serviceType', 'productionStep', 'payments.creator', 'journalEntry.items.account']);

        // Get next steps for this order based on current step
        $nextSteps = [];
        if ($serviceOrder->production_step_id) {
            $nextSteps = ProductionStep::where('parent_step_id', $serviceOrder->production_step_id)->get();
        } else {
            $nextSteps = ProductionStep::where('company_id', $serviceOrder->company_id)
                ->where('is_start', true)
                ->get();
        }

        return Inertia::render('service-orders/Show', [
            'order' => $serviceOrder,
            'next_steps' => $nextSteps,
        ]);
    }

    /**
     * Add an item to an existing order.
     */
    public function addItem(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'quantity' => 'required|numeric|min:0.001',
            'notes' => 'nullable|string',
        ]);

        $this->serviceOrderService->addItem(
            $serviceOrder,
            $validated['service_type_id'],
            $validated['quantity'],
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Item added to order.');
    }

    /**
     * Update the status of the service order.
     */
    public function updateStatus(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'status_code' => 'required|string',
        ]);

        $this->serviceOrderService->updateStatus($serviceOrder, $validated['status_code']);

        return back()->with('success', 'Status updated.');
    }

    /**
     * Update the production step of the service order (from Kanban).
     */
    public function updateStep(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'production_step_id' => 'required|exists:production_steps,id',
        ]);

        try {
            $this->serviceOrderService->updateProductionStep($serviceOrder, $validated['production_step_id']);

            return back()->with('success', 'Production step updated.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Store a new production step.
     */
    public function storeStep(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'parent_step_id' => 'nullable|exists:production_steps,id',
            'sequence_order' => 'required|integer',
            'is_start' => 'boolean',
            'is_final' => 'boolean',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['is_start'] = $request->boolean('is_start');
        $validated['is_final'] = $request->boolean('is_final');

        ProductionStep::create($validated);

        return back()->with('success', 'Step created successfully.');
    }

    /**
     * Remove a production step.
     */
    public function destroyStep(ProductionStep $step)
    {
        if (ServiceOrder::where('production_step_id', $step->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete step that is currently in use by orders.']);
        }

        $step->delete();

        return back()->with('success', 'Step deleted successfully.');
    }

    /**
     * Fast-track order to final step.
     */
    public function finalize(ServiceOrder $serviceOrder)
    {
        $finalStep = ProductionStep::where('company_id', $serviceOrder->company_id)
            ->where('is_final', true)
            ->first();

        if (! $finalStep) {
            return back()->withErrors(['error' => 'Belum ada step produksi yang ditandai sebagai "Step Akhir". Harap atur di Kanban Board.']);
        }

        try {
            $this->serviceOrderService->updateProductionStep($serviceOrder, $finalStep->id);

            return back()->with('success', "Order #{$serviceOrder->order_number} berhasil diselesaikan.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Record a payment for the service order.
     */
    public function recordPayment(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        // Convert to cents
        $amountCents = (int) ($validated['amount'] * 100);

        $this->serviceOrderService->recordPayment(
            $serviceOrder,
            $amountCents,
            $validated['payment_method'],
            $validated['notes'] ?? null
        );

        return back()->with('success', 'Payment recorded.');
    }

    /**
     * Void the service order.
     */
    public function void(Request $request, ServiceOrder $serviceOrder)
    {
        $request->validate([
            'reason' => 'required|string',
        ]);

        $this->serviceOrderService->void($serviceOrder, $request->reason);

        return back()->with('success', 'Order voided.');
    }

    /**
     * Adjust the price of a service order manually.
     */
    public function adjustPrice(Request $request, ServiceOrder $serviceOrder)
    {
        $validated = $request->validate([
            'total_amount' => 'required|numeric|min:0',
        ]);

        try {
            $this->serviceOrderService->adjustPrice(
                $serviceOrder,
                (int) ($validated['total_amount'] * 100)
            );

            return back()->with('success', 'Harga order berhasil disesuaikan.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
