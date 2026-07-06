<?php

namespace App\Http\Middleware;

use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        if (! UserRole::hasStaffAccess(auth()->user())) {
            abort(403, 'Akses hanya untuk petugas MCU.');
        }

        return $next($request);
    }
}
