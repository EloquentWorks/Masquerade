<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to block access to certain routes while masquerading.
 *
 * This middleware checks if the current user is masquerading. If they are, it aborts the request
 * with a 403 Forbidden response and a configurable message. This is useful for preventing users
 * from performing certain actions while impersonating another user.
 */
final class BlockMasquerade
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is currently masquerading
        if ($this->masquerade->isMasquerading()) {
            abort(403, (string) config('masquerade.messages.blocked', 'This action is blocked while masquerading.'));
        }

        // If not masquerading, continue processing the request
        return $next($request);
    }
}
