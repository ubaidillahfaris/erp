<?php

namespace App\Http\Middleware;

use App\Models\PeriodLock;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPeriodLock
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $date = $request->input('tanggal') ?? $request->input('date') ?? now();

        if (PeriodLock::isLocked($date)) {
            $dt = Carbon::parse($date);
            abort(403, "Periode Akuntansi {$dt->format('F Y')} sudah dikunci. Transaksi tidak dapat dilakukan.");
        }

        return $next($request);
    }
}
