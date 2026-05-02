<?php

namespace App\Http\Controllers;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ServiceCategory::where('company_id', auth()->user()->company_id)
            ->withCount('services');

        if ($request->search) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        return Inertia::render('settings/services/Categories', [
            'categories' => $query->latest()
                ->paginate(10)
                ->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $validated['company_id'] = auth()->user()->company_id;

        ServiceCategory::create($validated);

        return back()->with('success', 'Kategori jasa berhasil ditambahkan.');
    }

    public function update(Request $request, ServiceCategory $serviceCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        $serviceCategory->update($validated);

        return back()->with('success', 'Kategori jasa diperbarui.');
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        if ($serviceCategory->services()->exists()) {
            return back()->with('error', 'Kategori tidak bisa dihapus karena masih digunakan oleh beberapa jasa.');
        }

        $serviceCategory->delete();

        return back()->with('success', 'Kategori jasa dihapus.');
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:service_categories,id',
        ]);

        $ids = $request->ids;
        
        // Check if any category is in use
        $categoriesWithServices = ServiceCategory::whereIn('id', $ids)
            ->whereHas('services')
            ->withCount('services')
            ->get();

        if ($categoriesWithServices->isNotEmpty()) {
            $names = $categoriesWithServices->map(fn($c) => $c->name)->join(', ');
            return back()->with('error', "Kategori ($names) masih memiliki layanan aktif dan tidak dapat dihapus.");
        }

        ServiceCategory::whereIn('id', $ids)->delete();

        return back()->with('success', count($ids) . ' kategori berhasil dihapus.');
    }
}
