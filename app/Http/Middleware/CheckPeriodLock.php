<?php

namespace App\Http\Middleware;

use App\Models\PeriodLock;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPeriodLock
{
    public function handle(Request $request, Closure $next): Response
    {
        // Allow read-only access
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $date = $request->input('date') ?? $request->input('tanggal') ?? now();

        if (PeriodLock::isLocked($date)) {
            $dt = Carbon::parse($date);
            $monthName = $dt->translatedFormat('F');
            $message = "Periode Akuntansi {$monthName} {$dt->year} sudah dikunci. Transaksi tidak dapat dilakukan.";

            throw \Illuminate\Validation\ValidationException::withMessages([
                'period_lock' => $message
            ]);
        }

        return $next($request);
    }
}
