<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ShareMasqueradeContext
{
    /**
     * ShareMasqueradeContext constructor.
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
        // Share masquerade context with the request
        $request->attributes->set('masquerade.active', $this->masquerade->isMasquerading());
        $request->attributes->set('masquerade.context', $this->masquerade->context());
        $request->attributes->set('masquerade.session', $this->masquerade->session());

        // Share masquerade context with the view
        return $next($request);
    }
}
