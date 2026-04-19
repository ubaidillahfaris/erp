<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayableController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payable::with(['party', 'createdBy']);

        // Filters
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('reference_type', 'like', "%{$request->search}%")
                    ->orWhere('notes', 'like', "%{$request->search}%");
            });
        }

        if ($request->type && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->date_start) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->date_end) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        // Sorting
        $sort = $request->sort ?? 'created_at';
        $direction = $request->direction ?? 'desc';
        $query->orderBy($sort, $direction);

        // Summary
        $summary = [
            'total_payable' => Payable::where('type', 'payable')->where('status', '!=', 'paid')->sum('remaining_amount'),
            'total_receivable' => Payable::where('type', 'receivable')->where('status', '!=', 'paid')->sum('remaining_amount'),
            'overdue_count' => Payable::where('status', 'overdue')->count(),
        ];

        return Inertia::render('Payables/Index', [
            'payables' => $query->paginate($request->per_page ?? 15)->withQueryString(),
            'filters' => $request->only(['search', 'type', 'status', 'date_start', 'date_end', 'per_page', 'sort', 'direction']),
            'summary' => $summary,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payable $payable)
    {
        $payable->load(['payments.createdBy', 'interestSchedules', 'createdBy']);

        // Fetch Party
        $party = null;
        if ($payable->party_type === 'customer') {
            $party = Customer::find($payable->party_id);
        } elseif ($payable->party_type === 'vendor') {
            $party = Vendor::find($payable->party_id);
        }

        // Fetch Reference (Dynamic)
        $reference = null;
        if ($payable->reference_type === 'sale') {
            $reference = DB::table('sales')->where('id', $payable->reference_id)->first();
        } elseif ($payable->reference_type === 'restock') {
            $reference = DB::table('restocks')->where('id', $payable->reference_id)->first();
        }

        return Inertia::render('Payables/Show', [
            'payable' => $payable,
            'party' => $party,
            'reference' => $reference,
        ]);
    }

    /**
     * Store a newly created payment in storage.
     */
    public function storePayment(Request $request, Payable $payable)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $payable->remaining_amount,
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $payable) {
            // Create Payment - observer in Payment model will update Payable status/paid_amount/remaining_amount
            Payment::create([
                'payable_id' => $payable->id,
                'amount' => $request->amount,
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'recorded_by' => auth()->id(),
            ]);
        });

        return redirect()->back()->with('success', 'Pembayaran berhasil dicatat');
    }
}
