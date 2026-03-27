<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreRoleRequest;
use App\Http\Requests\Settings\UpdateRoleRequest;
use App\Models\Menu;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService) {}

    /**
     * Display a listing of the roles.
     */
    public function index(): Response
    {
        return Inertia::render('settings/Roles', [
            'roles' => Role::with('permissions')->get()->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name'),
                'menu_ids' => Menu::whereIn('permission_name', $role->permissions->pluck('name'))
                    ->pluck('id'),
            ]),
            'menus' => Menu::with('children')->root()->active()->orderBy('order_priority')->get(),
        ]);
    }

    /**
     * Store a newly created role in storage.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->name]);

        if ($request->has('menu_ids')) {
            $this->roleService->syncRolePermissionsFromMenus($role, $request->menu_ids);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil ditambahkan.');
    }

    /**
     * Update the specified role in storage.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update(['name' => $request->name]);

        if ($request->has('menu_ids')) {
            $this->roleService->syncRolePermissionsFromMenus($role, $request->menu_ids);
        }

        return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the specified role from storage.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['superadmin', 'cashier'])) {
            return back()->with('error', 'Role sistem tidak dapat dihapus.');
        }

        $role->delete();

        return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
    }
}
