<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateSignatureWhileIgnoring
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$ignore): Response
    {
        // Default parameters to ignore if none specified
        if (empty($ignore)) {
            $ignore = ['page'];
        }

        // Validate signature while ignoring specified parameters
        if (! $request->hasValidSignatureWhileIgnoring($ignore)) {
            // Redirect to error page or abort
            if ($request->expectsJson()) {
                abort(403, 'Invalid or expired signature.');
            }

            return redirect()->route('approval-link-invalid');
        }

        return $next($request);
    }
}
