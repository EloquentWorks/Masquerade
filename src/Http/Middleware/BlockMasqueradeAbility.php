<?php

namespace EloquentWorks\Masquerade\Http\Middleware;

use Closure;
use EloquentWorks\Masquerade\Exceptions\MasqueradeAbilityBlockedException;
use EloquentWorks\Masquerade\MasqueradeManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BlockMasqueradeAbility
{
    /**
     * BlockMasqueradeAbility constructor.
     *
     * @return void
     */
    public function __construct(
        private readonly MasqueradeManager $masquerade,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        // Check if the specified ability is blocked while masquerading
        if ($this->masquerade->blocksAbility($ability)) {
            $this->masquerade->recordBlockedAbility($ability, reason: 'Blocked by middleware.');

            // Return a JSON response if the request expects JSON, otherwise abort with a 403 error
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => MasqueradeAbilityBlockedException::forAbility($ability)->getMessage(),
                    'ability' => $ability,
                ], 403);
            }

            // Abort with a 403 error and a message indicating that the ability is blocked
            abort(403, MasqueradeAbilityBlockedException::forAbility($ability)->getMessage());
        }

        // If the ability is not blocked, continue processing the request
        return $next($request);
    }
}
