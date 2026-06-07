<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceSessionConfig
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Override session config untuk IP access dan production
        if (app()->environment('production') || app()->environment('staging')) {
            // Detect if accessing via IP address (not domain)
            $host = $request->getHost();
            
            // If accessing via IP or localhost, disable secure cookies
            if (filter_var($host, FILTER_VALIDATE_IP) || 
                str_contains($host, 'localhost') || 
                str_contains($host, '127.0.0.1')) {
                
                config([
                    'session.secure' => false,
                    'session.domain' => null,
                    'session.same_site' => 'lax',
                ]);
                
                // Log for debugging
                if (config('app.debug')) {
                    \Log::info('ForceSessionConfig: Adjusting session for IP access', [
                        'host' => $host,
                        'secure' => false,
                        'domain' => null,
                        'same_site' => 'lax',
                    ]);
                }
            }
        }

        return $next($request);
    }
}

