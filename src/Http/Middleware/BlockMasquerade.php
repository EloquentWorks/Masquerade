<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to block access to certain routes while masquerading.
 */
final class BlockMasquerade
{
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is currently masquerading
        if ($this->masquerade->isMasquerading()) {
            abort(403, (string) config('masquerade.messages.blocked', 'This action is blocked while masquerading.'));
        }

        // If not masquerading, allow the request to proceed
        return $next($request);
    }
}
