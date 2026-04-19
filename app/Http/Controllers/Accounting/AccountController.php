<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountRequest;
use App\Http\Requests\Accounting\UpdateAccountRequest;
use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $perPage = $request->integer('per_page', 15);
        $sort = $request->input('sort') ?: 'code';
        $direction = str_contains(strtolower($request->input('direction', 'asc')), 'desc') ? 'desc' : 'asc';

        $query = Account::query()
            ->withCount('journalItems');

        // Filters
        $query->when($request->search, function ($q, $search) {
            $q->where('code', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%");
        });

        $query->when($request->type, function ($q, $type) {
            $q->where('type', $type);
        });

        $query->when($request->has('is_active') && $request->is_active !== 'all', function ($q) use ($request) {
            $q->where('is_active', $request->boolean('is_active'));
        });

        $accounts = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('accounting/accounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'type', 'is_active', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('accounting/accounts/Create');
    }

    public function store(StoreAccountRequest $request)
    {
        Account::create($request->validated());

        \Illuminate\Support\Facades\Cache::forget('trial_balance_current');

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil ditambahkan.');
    }

    public function show(Account $account): Response
    {
        return Inertia::render('accounting/accounts/Show', [
            'account' => $account->loadCount('journalItems'),
        ]);
    }

    public function edit(Account $account): Response
    {
        return Inertia::render('accounting/accounts/Edit', [
            'account' => $account->loadCount('journalItems'),
        ]);
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        $account->loadCount('journalItems');

        if ($account->journal_items_count > 0) {
            // Check if restricted fields are being changed
            $isCodeChanged = $request->input('code') !== $account->code;
            $isTypeChanged = $request->input('type') !== $account->type;
            $isBalanceTypeChanged = $request->input('balance_type') !== $account->balance_type;

            if ($isCodeChanged || $isTypeChanged || $isBalanceTypeChanged) {
                throw ValidationException::withMessages([
                    'code' => ['Data akun (code/type/balance) tidak dapat diubah karena memiliki riwayat jurnal.'],
                ]);
            }
        }

        $account->update($request->validated());

        \Illuminate\Support\Facades\Cache::forget('trial_balance_current');

        return redirect()->route('accounts.index')
            ->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroy(Request $request, Account $account)
    {
        $account->loadCount('journalItems');

        if ($account->journal_items_count > 0) {
            return abort(422, 'Tidak dapat menghapus akun yang memiliki riwayat jurnal.');
        }

        if ($request->boolean('force')) {
            $account->delete();
            $message = 'Akun berhasil dihapus permanen.';
        } else {
            $account->update(['is_active' => false]);
            $message = 'Akun dinonaktifkan.';
        }

        \Illuminate\Support\Facades\Cache::forget('trial_balance_current');

        return redirect()->route('accounts.index')->with('success', $message);
    }
}
