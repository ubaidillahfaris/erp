<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ProductionStep;
use App\Models\Service;
use App\Models\ServiceOrder;
use App\Models\ServiceProcessingStatus;
use App\Models\Vendor;
use App\Services\ServiceOrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceOrderController extends Controller
{
    public function __construct(protected ServiceOrderService $serviceOrderService)
    {
    }

    /**
     * Display a listing of service orders.
     */
    public function index(Request $request): Response
    {
        $query = ServiceOrder::with(['service', 'party', 'productionStep'])
            ->orderBy('created_at', 'desc');

        if ($request->service_id) {
            $query->where('service_id', $request->service_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return Inertia::render('service-orders/Index', [
            'orders' => $query->paginate(10)->withQueryString(),
            'services' => Service::all(),
            'steps' => ProductionStep::select('id', 'name', 'code')->orderBy('sequence_order')->get(),
            'filters' => $request->only(['search', 'status', 'date_start', 'date_end']),
        ]);
    }

    /**
     * Display a kanban board of service orders.
     */
    public function board(Request $request): Response
    {
        return Inertia::render('service-orders/Board', [
            'steps' => ProductionStep::orderBy('sequence_order')->get(),
            'orders' => ServiceOrder::with(['party', 'productionStep'])
                ->where('status', '!=', 'cancelled')
                ->where('status', '!=', 'posted')
                ->get(),
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
            $nextSteps = \App\Models\ProductionStep::where('parent_step_id', $serviceOrder->production_step_id)->get();
        } else {
            $nextSteps = \App\Models\ProductionStep::where('company_id', $serviceOrder->company_id)
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
        \App\Models\ProductionStep::create($validated);

        return back()->with('success', 'Step created successfully.');
    }

    /**
     * Remove a production step.
     */
    public function destroyStep(\App\Models\ProductionStep $step)
    {
        if (ServiceOrder::where('production_step_id', $step->id)->exists()) {
            return back()->withErrors(['error' => 'Cannot delete step that is currently in use by orders.']);
        }

        $step->delete();
        return back()->with('success', 'Step deleted successfully.');
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
