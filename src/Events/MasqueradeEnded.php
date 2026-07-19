<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event triggered when a masquerade session ends.
 *
 * This event is dispatched when a user stops impersonating another user, either by logging out
 * of the impersonated session or by explicitly ending the masquerade session.
 */
final class MasqueradeEnded
{
    /**
     * Create a new event instance.
     *
     * @param  Authenticatable|null  $impersonator  The user who was impersonating another user, or null if the impersonator is not available.
     * @param  mixed  $target  The user who was being impersonated.
     * @param  string  $guard  The authentication guard that was used for the impersonation.
     * @param  string  $uuid  A unique identifier for the masquerade session.
     */
    public function __construct(
        public readonly ?Authenticatable $impersonator,
        public readonly mixed $target,
        public readonly string $guard,
        public readonly string $uuid,
    ) {}
}
