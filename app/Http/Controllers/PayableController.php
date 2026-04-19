<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Nasabah;
use App\Models\Payable;
use App\Models\Payment;
use App\Models\Restock;
use App\Models\Sale;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PayableController extends Controller
{
    /**
     * Display a listing of payables/receivables.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sort = $request->input('sort', 'created_at');
        $direction = str_contains(strtolower($request->input('direction', 'desc')), 'asc') ? 'asc' : 'desc';

        $query = Payable::query()
            ->with(['interestSchedules', 'createdBy'])
            ->withSum('payments', 'amount');

        // Filters
        $query->when($request->type, fn ($q, $type) => $q->where('type', $type));
        $query->when($request->status, fn ($q, $status) => $q->where('status', $status));

        $query->when($request->date_start, fn ($q, $date) => $q->whereDate('created_at', '>=', $date));
        $query->when($request->date_end, fn ($q, $date) => $q->whereDate('created_at', '<=', $date));

        $query->when($request->search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('party_type', 'vendor')
                        ->whereIn('party_id', Vendor::where('name', 'like', "%{$search}%")->pluck('id'));
                })->orWhere(function ($q2) use ($search) {
                    $q2->where('party_type', 'customer')
                        ->whereIn('party_id', Customer::where('name', 'like', "%{$search}%")->pluck('id'));
                });
            });
        });

        $payables = $query->orderBy($sort, $direction)
            ->paginate($perPage)
            ->through(function ($payable) {
                $paidAmount = (float) ($payable->payments_sum_amount ?? 0);
                $payable->paid_amount = $paidAmount;
                $payable->remaining_amount = (float) $payable->total_amount - $paidAmount;

                // Resolve party name for list view convenience
                if ($payable->party_type === 'vendor') {
                    $payable->party_name = Vendor::find($payable->party_id)?->name ?? 'Unknown Vendor';
                } else {
                    $payable->party_name = Customer::find($payable->party_id)?->name ?? 'Unknown Customer';
                }

                return $payable;
            })
            ->withQueryString();

        $summary = [
            'total_payable' => Payable::where('type', 'payable')->where('status', '!=', 'paid')->sum('total_amount'),
            'total_receivable' => Payable::where('type', 'receivable')->where('status', '!=', 'paid')->sum('total_amount'),
            'overdue_count' => Payable::where('status', 'overdue')->count(),
        ];

        return Inertia::render('Payables/Index', [
            'payables' => $payables,
            'filters' => $request->only(['type', 'status', 'search', 'date_start', 'date_end', 'per_page', 'sort', 'direction']),
            'summary' => $summary,
        ]);
    }

    /**
     * Display the specified payable/receivable.
     */
    public function show($id)
    {
        $payable = Payable::with([
            'payments.recordedBy',
            'interestSchedules',
            'paymentReminders',
            'createdBy',
        ])->withSum('payments', 'amount')->findOrFail($id);

        $paidAmount = (float) ($payable->payments_sum_amount ?? 0);
        $payable->paid_amount = $paidAmount;
        $payable->remaining_amount = (float) $payable->total_amount - $paidAmount;

        // Resolve Party
        $party = null;
        if ($payable->party_type === 'vendor') {
            $party = Vendor::find($payable->party_id);
        } elseif ($payable->party_type === 'customer') {
            $party = Customer::find($payable->party_id);
        }

        // Resolve Reference
        $reference = null;
        if ($payable->reference_type === 'restock') {
            $reference = Restock::find($payable->reference_id);
        } elseif ($payable->reference_type === 'sale') {
            $reference = Sale::find($payable->reference_id);
        } elseif ($payable->reference_type === 'nasabah') {
            $reference = Nasabah::find($payable->reference_id);
        }

        return Inertia::render('Payables/Show', [
            'payable' => $payable,
            'party' => $party,
            'reference' => $reference,
        ]);
    }

    /**
     * Store a payment for the specified payable/receivable.
     */
    public function storePayment(Request $request, $payableId)
    {
        $payable = Payable::withSum('payments', 'amount')->findOrFail($payableId);
        $paidAmount = (float) ($payable->payments_sum_amount ?? 0);
        $remainingAmount = (float) $payable->total_amount - $paidAmount;

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$remainingAmount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ], [
            'amount.max' => 'Jumlah pembayaran melebihi sisa tagihan (Sisa: '.number_format($remainingAmount, 2).').',
        ]);

        Payment::create([
            'payable_id' => $payable->id,
            'amount' => $validated['amount'],
            'payment_date' => $validated['payment_date'],
            'payment_method' => $validated['payment_method'],
            'notes' => $validated['notes'],
            'recorded_by' => Auth::id(),
        ]);

        return back()->with('success', 'Pembayaran sebesar '.number_format($validated['amount'], 2).' berhasil dicatat.');
    }
}
