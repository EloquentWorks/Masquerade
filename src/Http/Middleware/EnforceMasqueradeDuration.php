<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce masquerade session duration.
 *
 * This middleware checks if the current masquerade session has expired. If it has, it stops the
 * masquerade session and either returns a JSON response or redirects the user, depending on the
 * request type. This ensures that users cannot continue to impersonate another user beyond the
 * allowed duration.
 */
final class EnforceMasqueradeDuration
{
    /**
     * Create a new middleware instance.
     *
     * @param  MasqueradeManager  $masquerade
     */
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure(Request): Response  $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the masquerade session has expired and stop it if necessary
        if ($this->masquerade->stopIfExpired()) {
            // If the request expects a JSON response, return a JSON error message with a 419 status code
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => config('masquerade.messages.expired', 'Your masquerade session has expired.'),
                ], 419);
            }

            // If the request does not expect JSON, redirect the user to a configured route with a status message
            return redirect(config('masquerade.routes.redirect_after_stop', '/'))
                ->with('status', config('masquerade.messages.expired'));
        }

        // If the masquerade session has not expired, continue processing the request
        return $next($request);
    }
}
