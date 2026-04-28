<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\FixedAsset;
use App\Services\DepreciationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FixedAssetController extends Controller
{
    public function __construct(
        private DepreciationService $depreciationService
    ) {}

    public function index(Request $request): Response
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort', 'acquisition_date');
        $direction = $request->input('direction', 'desc');

        $query = FixedAsset::query();

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('asset_code', 'like', '%'.$request->search.'%')
                    ->orWhere('category', 'like', '%'.$request->search.'%');
            });
        }

        $assets = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('fixed-asset/Index', [
            'assets' => $assets,
            'filters' => $request->only(['search', 'per_page', 'sort', 'direction']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('fixed-asset/Create', [
            'accounts' => Account::orderBy('code')->get(),
            'asset_accounts' => Account::where('code', 'like', '14%')->where('type', 'asset')->get(),
            'expense_accounts' => Account::where('type', 'expense')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:255',
            'acquisition_date' => 'required|date',
            'acquisition_cost' => 'required|integer|min:0',
            'useful_life_months' => 'required|integer|min:1',
            'salvage_value' => 'required|integer|min:0',
            'asset_account_id' => 'required|exists:accounts,id',
            'depreciation_account_id' => 'required|exists:accounts,id',
            'expense_account_id' => 'required|exists:accounts,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $asset = FixedAsset::create([
                ...$validated,
                'current_book_value' => $validated['acquisition_cost'],
                'status' => 'active',
            ]);

            $this->depreciationService->generateSchedule($asset);

            return redirect()->route('fixed-assets.show', $asset->id)
                ->with('success', 'Aset tetap berhasil ditambahkan dan jadwal penyusutan telah dibuat.');
        });
    }

    public function show(FixedAsset $fixedAsset): Response
    {
        $fixedAsset->load(['assetAccount', 'depreciationAccount', 'expenseAccount', 'schedules.journalEntry']);

        return Inertia::render('fixed-asset/Show', [
            'asset' => $fixedAsset,
        ]);
    }

    public function dispose(Request $request, FixedAsset $fixedAsset)
    {
        $request->validate([
            'disposal_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $fixedAsset->update([
            'status' => 'disposed',
            'description' => ($fixedAsset->description ? $fixedAsset->description."\n" : '').'Disposed on '.$request->disposal_date.'. '.$request->notes,
        ]);

        // Cancel future scheduled depreciations
        $fixedAsset->schedules()->where('status', 'scheduled')->delete();

        return redirect()->back()->with('success', 'Aset telah dihentikan (disposed).');
    }
}
