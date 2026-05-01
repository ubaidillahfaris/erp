<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureHasCompany
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (Auth::check() && ! $user->company_id && ! $user->hasRole('superadmin') && ! $request->is('onboarding*')) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
