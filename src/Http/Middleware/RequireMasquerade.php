<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require a masquerade session for certain routes.
 *
 * This middleware checks if the current user is masquerading. If they are not, it aborts the request
 * with a 403 Forbidden response and a message indicating that a masquerade session is required. This
 * is useful for protecting routes that should only be accessible while impersonating another user.
 */
final class RequireMasquerade
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
        // Check if the user is currently masquerading
        if (! $this->masquerade->isMasquerading()) {
            abort(403, 'A masquerade session is required for this route.');
        }

        // If the user is masquerading, continue processing the request
        return $next($request);
    }
}
