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
use Illuminate\Support\Facades\Storage;
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
            'app_debug' => config('app.debug'),
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
            if ($request->boolean('create_user') && ! $userId) {
                $user = User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?? strtolower(str_replace(' ', '.', $validated['name'])).'@warung.com',
                    'password' => Hash::make($validated['password']),
                ]);

                if ($request->filled('role')) {
                    $user->assignRole($validated['role']);
                }

                $userId = $user->id;
            }

            if ($request->hasFile('photo')) {
                $validated['photo_path'] = $request->file('photo')->store('employees/photos', 'public');
            }

            $employee = Employee::create($validated);

            // Handle additional documents
            if ($request->has('documents_meta')) {
                $docsMeta = json_decode($request->input('documents_meta'), true);
                if ($request->hasFile('documents')) {
                    $files = $request->file('documents');
                    foreach ($files as $index => $file) {
                        $meta = $docsMeta[$index] ?? ['type' => 'Lainnya'];
                        $employee->documents()->create([
                            'type' => $meta['type'],
                            'file_name' => $file->getClientOriginalName(),
                            'file_path' => $file->store('employees/documents', 'public'),
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                        ]);
                    }
                }
            }

            return redirect()->route('employees.index')
                ->with('success', 'Data pegawai berhasil ditambahkan.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = Employee::with(['user.roles', 'documents'])->findOrFail($id);

        return Inertia::render('employees/Show', [
            'employee' => $employee,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $employee = Employee::with(['user.roles', 'documents'])->findOrFail($id);

        return Inertia::render('employees/Create', [
            'employee' => $employee,
            'roles' => Role::all()->pluck('name'),
            'isEditing' => true,
            'app_debug' => config('app.debug'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $validated = $request->validated();

        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }
            $validated['photo_path'] = $request->file('photo')->store('employees/photos', 'public');
        }

        $employee->update($validated);

        // Handle additional documents
        if ($request->has('documents_meta')) {
            $docsMeta = json_decode($request->input('documents_meta'), true);
            if ($request->hasFile('documents')) {
                $files = $request->file('documents');
                foreach ($files as $index => $file) {
                    $meta = $docsMeta[$index] ?? ['type' => 'Lainnya'];
                    $employee->documents()->create([
                        'type' => $meta['type'],
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $file->store('employees/documents', 'public'),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }
            }
        }

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
