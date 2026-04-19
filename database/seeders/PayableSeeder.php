<?php

namespace Database\Seeders;

use App\Models\Payable;
use App\Models\Payment;
use App\Models\InterestSchedule;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class PayableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $customer = Customer::first() ?? Customer::factory()->create();
        $vendor = Vendor::first() ?? Vendor::factory()->create();

        // 1. Hutang ke Vendor (Restock) - Status Partial
        $payable1 = Payable::create([
            'type' => 'payable',
            'reference_type' => 'restock',
            'reference_id' => 101,
            'party_type' => 'vendor',
            'party_id' => $vendor->id,
            'principal_amount' => 5000000,
            'total_interest' => 0,
            'total_amount' => 5000000,
            'due_date' => Carbon::now()->addDays(14),
            'status' => 'partial',
            'notes' => 'Pembelian bahan baku plastik',
            'created_by' => $user->id,
        ]);

        Payment::create([
            'payable_id' => $payable1->id,
            'amount' => 2000000,
            'payment_date' => Carbon::now()->subDays(2),
            'payment_method' => 'transfer',
            'notes' => 'DP Pembelian',
            'recorded_by' => $user->id,
        ]);

        // 2. Piutang dari Customer (Sale) - Status Overdue
        $payable2 = Payable::create([
            'type' => 'receivable',
            'reference_type' => 'sale',
            'reference_id' => 505,
            'party_type' => 'customer',
            'party_id' => $customer->id,
            'principal_amount' => 1250000,
            'total_interest' => 50000,
            'total_amount' => 1300000,
            'due_date' => Carbon::now()->subDays(5),
            'status' => 'overdue',
            'notes' => 'Penjualan Nota #505',
            'created_by' => $user->id,
        ]);

        // 3. Hutang dengan Bunga & Cicilan (Nasabah/Modal)
        $payable3 = Payable::create([
            'type' => 'payable',
            'reference_type' => 'nasabah',
            'reference_id' => 1,
            'party_type' => 'vendor', // Assume vendor as funding source
            'party_id' => $vendor->id,
            'principal_amount' => 10000000,
            'interest_type' => 'percentage',
            'interest_rate' => 2.5,
            'interest_period' => 'monthly',
            'installment_count' => 3,
            'total_interest' => 750000,
            'total_amount' => 10750000,
            'due_date' => Carbon::now()->addMonths(3),
            'status' => 'open',
            'notes' => 'Pinjaman modal usaha',
            'created_by' => $user->id,
        ]);

        // Create Interest Schedules for Payable 3
        for ($i = 1; $i <= 3; $i++) {
            InterestSchedule::create([
                'payable_id' => $payable3->id,
                'period_number' => $i,
                'due_date' => Carbon::now()->addMonths($i),
                'principal_portion' => 10000000 / 3,
                'interest_portion' => 250000,
                'total_due' => (10000000 / 3) + 250000,
                'status' => 'pending',
            ]);
        }
    }
}
