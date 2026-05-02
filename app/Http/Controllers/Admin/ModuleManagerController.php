<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ModuleManagerController extends Controller
{
    public function index(Request $request)
    {
        $query = Module::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('slug', 'like', '%' . $request->search . '%');
        }

        $modules = $query->orderBy('order_priority')
                         ->paginate($request->per_page ?? 10)
                         ->withQueryString();

        return Inertia::render('Admin/System/Modules/Index', [
            'modules' => $modules,
            'filters' => $request->only(['search', 'per_page']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules,slug',
            'icon' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:20',
            'order_priority' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        Module::create($validated);

        return back()->with('success', 'Module registered successfully.');
    }

    public function update(Request $request, Module $module)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:modules,slug,' . $module->id,
            'icon' => 'nullable|string|max:255',
            'version' => 'nullable|string|max:20',
            'order_priority' => 'required|integer',
            'is_active' => 'boolean',
        ]);

        $module->update($validated);

        return back()->with('success', 'Module updated successfully.');
    }

    public function toggle(Module $module)
    {
        if ($module->slug === 'platform') {
            return back()->with('error', 'Cannot disable core platform module.');
        }

        $module->update([
            'is_active' => !$module->is_active,
        ]);

        return back()->with('success', "Module {$module->name} status updated.");
    }

    public function destroy(Module $module)
    {
        if ($module->slug === 'platform') {
            return back()->with('error', 'Cannot delete core platform module.');
        }

        $module->delete();

        return back()->with('success', 'Module deleted successfully.');
    }
}
