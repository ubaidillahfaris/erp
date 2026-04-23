<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $search = $request->input('search');
        $sort = $request->input('sort') ?: 'created_at';
        $direction = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Employee::query()
            ->with('user.roles')
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('nik', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");
            });

        $employees = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('employees/Index', [
            'employees' => $employees,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('employees/Create', [
            'roles' => Role::all()->pluck('name'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            
            $userId = $validated['user_id'] ?? null;

            // Handle automatic user creation if switch is on
            if ($request->boolean('create_user') && !$userId) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? strtolower(str_replace(' ', '.', $validated['name'])) . '@warung.com',
                    'password' => Hash::make($validated['password']),
                ]);

                if ($request->filled('role')) {
                    $user->assignRole($validated['role']);
                }
                
                $userId = $user->id;
            }

            $validated['user_id'] = $userId;
            
            Employee::create($validated);

            return redirect()->route('employees.index')
                ->with('success', 'Data pegawai berhasil ditambahkan.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = Employee::with(['user.roles'])->findOrFail($id);

        return Inertia::render('employees/Show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $employee = Employee::with(['user.roles'])->findOrFail($id);

        return Inertia::render('employees/Create', [
            'employee' => $employee,
            'roles' => Role::all()->pluck('name'),
            'isEditing' => true,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $validated = $request->validated();
        
        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Data pegawai berhasil dihapus.');
    }
}
