<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce masquerade duration and stop masquerading if expired.
 */
final class EnforceMasqueradeDuration
{
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the masquerade session has expired and stop it if necessary
        if ($this->masquerade->stopIfExpired()) {
            // Return a JSON response if the request expects JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => config('masquerade.messages.expired', 'Your masquerade session has expired.'),
                ], 419);
            }

            // Redirect to the configured route after stopping masquerade
            return redirect(config('masquerade.routes.redirect_after_stop', '/'))
                ->with('status', config('masquerade.messages.expired'));
        }

        // If the masquerade session is still valid, allow the request to proceed
        return $next($request);
    }
}
