<?php

namespace App\Http\Controllers;

use App\Models\ProductionStep;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceOrder;
use App\Models\ServicePricing;
use App\Models\ServiceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    /**
     * Display a listing of services.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('settings/services/Index', [
            'services' => Service::with(['category'])->withCount(['serviceTypes', 'orders'])->paginate(10),
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Show the form for creating a new service.
     */
    public function create(): Response
    {
        $categories = ServiceCategory::where('company_id', auth()->user()->company_id)->get();

        return Inertia::render('settings/services/Create', [
            'available_categories' => $categories,
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:services,code',
            'name' => 'required|string',
            'description' => 'nullable|string',
            'service_category_id' => 'required|exists:service_categories,id',
        ]);

        $validated['created_by'] = auth()->id();
        $service = Service::create($validated);

        return redirect()->route('settings.services.show', $service)
            ->with('success', 'Service created successfully.');
    }

    /**
     * Display the specified service and its configuration.
     */
    public function show(Service $service): Response
    {
        return Inertia::render('settings/services/Show', [
            'service' => $service->load(['serviceTypes.pricings', 'category']),
            'production_steps' => ProductionStep::where('company_id', auth()->user()->company_id)
                ->orderBy('sequence_order')
                ->get(),
        ]);
    }

    /**
     * Store a new service type.
     */
    public function storeType(Request $request, Service $service)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'name' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $service->serviceTypes()->create($validated);

        return back()->with('success', 'Service type added.');
    }

    public function storePricing(Request $request, ServiceType $serviceType)
    {
        $validated = $request->validate([
            'pricing_basis' => 'required|string|in:per_kg,per_item,per_unit',
            'unit_name' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Convert to cents
        $validated['unit_price'] = (int) ($validated['unit_price'] * 100);

        $serviceType->pricings()->create($validated);

        return back()->with('success', 'Pricing rule added.');
    }

    /**
     * Update a pricing rule.
     */
    public function updatePricing(Request $request, ServicePricing $pricing)
    {
        $validated = $request->validate([
            'pricing_basis' => 'required|string|in:per_kg,per_item,per_unit',
            'unit_name' => 'required|string',
            'unit_price' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'discount_pct' => 'nullable|numeric|min:0|max:100',
        ]);

        // Convert to cents
        $validated['unit_price'] = (int) ($validated['unit_price'] * 100);

        $pricing->update($validated);

        return back()->with('success', 'Pricing rule updated.');
    }

    /**
     * Delete a pricing rule.
     */
    public function destroyPricing(ServicePricing $pricing)
    {
        $pricing->delete();

        return back()->with('success', 'Pricing rule deleted.');
    }

    /**
     * Sync production steps for the company.
     */
    public function syncStatuses(Request $request, Service $service)
    {
        $validated = $request->validate([
            'statuses' => 'present|array',
            'statuses.*.id' => 'nullable|integer',
            'statuses.*.code' => 'required|string',
            'statuses.*.name' => 'required|string',
            'statuses.*.sequence_order' => 'required|integer',
            'statuses.*.is_start' => 'boolean',
            'statuses.*.is_final' => 'boolean',
        ]);

        $companyId = auth()->user()->company_id;

        DB::transaction(function () use ($companyId, $validated) {
            // Get current step IDs to see which ones were removed
            $currentIds = collect($validated['statuses'])->pluck('id')->filter()->toArray();

            // Delete steps that were removed (and not in use)
            ProductionStep::where('company_id', $companyId)
                ->whereNotIn('id', $currentIds)
                ->each(function ($step) {
                    if (! ServiceOrder::where('production_step_id', $step->id)->exists()) {
                        $step->delete();
                    }
                });

            // Update or create steps
            foreach ($validated['statuses'] as $statusData) {
                ProductionStep::updateOrCreate(
                    [
                        'id' => $statusData['id'] > 0 ? $statusData['id'] : null,
                        'company_id' => $companyId,
                    ],
                    [
                        'code' => $statusData['code'],
                        'name' => $statusData['name'],
                        'sequence_order' => $statusData['sequence_order'],
                        'is_start' => $statusData['is_start'] ?? false,
                        'is_final' => $statusData['is_final'] ?? false,
                    ]
                );
            }
        });

        return back()->with('success', 'Alur kerja produksi diperbarui.');
    }
}
