<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Payable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class AgingReportController extends Controller
{
    public function index(Request $request)
    {
        $asOfDateRaw = $request->as_of_date ?? now()->toDateString();
        $asOfDate = Carbon::parse($asOfDateRaw)->startOfDay();
        $isToday = $asOfDate->isToday();
        
        $type = $request->type ?? 'all';
        $partyType = $request->party_type ?? 'all';
        $bucketFilter = $request->bucket ?? 'all';

        $fetchData = function () use ($asOfDate) {
            $payables = Payable::with(['interestSchedules', 'party'])
                ->whereIn('status', ['open', 'partial', 'overdue'])
                ->get();

            $lines = [];
            
            foreach ($payables as $payable) {
                if ($payable->installment_count > 1 && $payable->interestSchedules->isNotEmpty()) {
                    // Level 2: Schedule aging
                    foreach ($payable->interestSchedules as $schedule) {
                        if ($schedule->status === 'paid') {
                            continue;
                        }
                        
                        $dueDate = Carbon::parse($schedule->due_date);
                        // days_overdue = today.diffInDays(schedule.due_date, false)
                        // If as_of_date is 2026-04-21 and due_date is 2026-04-20 -> 1 day overdue
                        $daysOverdue = (int) $dueDate->diffInDays($asOfDate, false);
                        
                        $bucket = $this->getBucket($daysOverdue);
                        
                        $lines[] = [
                            'party_name' => $payable->party?->name ?? 'Unknown',
                            'party_type' => $payable->party_type,
                            'reference' => $payable->reference_type . ' #' . $payable->reference_id,
                            'type' => $payable->type,
                            'days_overdue' => $daysOverdue,
                            'bucket' => $bucket,
                            'amount' => (float) $schedule->total_due, // Assuming fully unpaid if status != paid
                            'due_date' => $schedule->due_date->toDateString(),
                            'payable_id' => $payable->id,
                        ];
                    }
                } else {
                    // Level 1: Simple aging
                    $dueDate = Carbon::parse($payable->due_date);
                    $daysOverdue = (int) $dueDate->diffInDays($asOfDate, false);
                    
                    $bucket = $this->getBucket($daysOverdue);
                    
                    $lines[] = [
                        'party_name' => $payable->party?->name ?? 'Unknown',
                        'party_type' => $payable->party_type,
                        'reference' => $payable->reference_type . ' #' . $payable->reference_id,
                        'type' => $payable->type,
                        'days_overdue' => $daysOverdue,
                        'bucket' => $bucket,
                        'amount' => (float) $payable->remaining_amount,
                        'due_date' => $payable->due_date ? $payable->due_date->toDateString() : null,
                        'payable_id' => $payable->id,
                    ];
                }
            }
            
            return $lines;
        };

        // Constraint 2: Skip cache if as_of_date is not today
        if ($isToday) {
            $allLines = Cache::remember('aging_report', 300, $fetchData);
        } else {
            $allLines = $fetchData();
        }

        // Apply filters
        $filteredLines = collect($allLines)->filter(function($line) use ($type, $partyType, $bucketFilter) {
            if ($type !== 'all' && $line['type'] !== $type) {
                return false;
            }
            if ($partyType !== 'all' && $line['party_type'] !== $partyType) {
                return false;
            }
            if ($bucketFilter !== 'all' && $line['bucket'] !== $bucketFilter) {
                return false;
            }
            return true;
        })->sortByDesc('days_overdue')->values();

        // Calculate summary
        $summary = [
            'payable' => $this->initSummaryBucket(),
            'receivable' => $this->initSummaryBucket(),
        ];

        foreach ($allLines as $line) {
            $t = $line['type'];
            $b = $line['bucket'];
            if (isset($summary[$t])) {
                $summary[$t][$b] += $line['amount'];
                $summary[$t]['total'] += $line['amount'];
            }
        }

        return Inertia::render('accounting/Aging', [
            'aging_lines' => $filteredLines,
            'summary' => $summary,
            'buckets' => [
                'current' => 'Belum Jatuh Tempo',
                'days_30' => '1-30 Hari',
                'days_60' => '31-60 Hari',
                'days_90' => '61-90 Hari',
                'over_90' => '>90 Hari',
            ],
            'filters' => [
                'type' => $type,
                'party_type' => $partyType,
                'bucket' => $bucketFilter,
                'as_of_date' => $asOfDate->toDateString(),
            ],
            'as_of_date' => $asOfDate->toDateString(),
        ]);
    }

    private function getBucket($days)
    {
        if ($days <= 0) {
            return 'current';
        }
        if ($days <= 30) {
            return 'days_30';
        }
        if ($days <= 60) {
            return 'days_60';
        }
        if ($days <= 90) {
            return 'days_90';
        }
        return 'over_90';
    }

    private function initSummaryBucket()
    {
        return [
            'current' => 0,
            'days_30' => 0,
            'days_60' => 0,
            'days_90' => 0,
            'over_90' => 0,
            'total' => 0,
        ];
    }
}
