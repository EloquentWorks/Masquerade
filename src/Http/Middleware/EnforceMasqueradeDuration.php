<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to enforce masquerade duration.
 */
final class EnforceMasqueradeDuration
{
    /**
     * EnforceMasqueradeDuration constructor.
     *
     * @param  MasqueradeManager  $masquerade
     * @return void
     */
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the masquerade session has expired and stop it if necessary
        if ($this->masquerade->stopIfExpired()) {

            // Return a JSON response if the request expects JSON, otherwise redirect to a specified route
            // with a status message
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => config('masquerade.messages.expired', 'Your masquerade session has expired.'),
                ], 419);
            }

            // Redirect to a specified route with a status message
            return redirect(config('masquerade.routes.redirect_after_stop', '/'))
                ->with('status', config('masquerade.messages.expired'));
        }

        return $next($request);
    }
}
