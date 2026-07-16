<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return redirect()->route('login');
        }

        if (! UserRole::hasStaffAccess($user)) {
            abort(403, 'Akses hanya untuk petugas MCU.');
        }

        return $next($request);
    }
}
