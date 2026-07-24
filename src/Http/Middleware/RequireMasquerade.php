<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to require a masquerade session.
 */
final class RequireMasquerade
{
    /**
     * RequireMasquerade constructor.
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
        // Check if the user is currently masquerading
        if (! $this->masquerade->isMasquerading()) {
            abort(403, 'A masquerade session is required for this route.');
        }

        return $next($request);
    }
}
