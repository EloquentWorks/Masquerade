<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireMasquerade
{
    /**
     * RequireMasquerade constructor.
     *
     * @return void
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
        if (! $this->masquerade->isMasquerading()) {
            abort(403, 'A masquerade session is required for this route.');
        }

        // If the user is masquerading, allow the request to proceed
        return $next($request);
    }
}
