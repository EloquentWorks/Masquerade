<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to share masquerade context with the request.
 */
final class ShareMasqueradeContext
{
    /**
     * ShareMasqueradeContext constructor.
     *
     * @return void
     */
    public function __construct(private readonly MasqueradeManager $masquerade) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Share masquerade context with the request
        $request->attributes->set('masquerade.active', $this->masquerade->isMasquerading());
        $request->attributes->set('masquerade.context', $this->masquerade->context());
        $request->attributes->set('masquerade.session', $this->masquerade->session());

        return $next($request);
    }
}
