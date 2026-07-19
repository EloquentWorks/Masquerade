<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event triggered when a masquerade session starts.
 *
 * This event is dispatched when a user begins impersonating another user.
 */
final class MasqueradeStarted
{
    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $impersonator  The user who is impersonating another user.
     * @param  Authenticatable  $target  The user who is being impersonated.
     * @param  string  $guard  The authentication guard that is being used for the impersonation.
     * @param  string|null  $reason  The reason for starting the masquerade, if any.
     */
    public function __construct(
        public readonly Authenticatable $impersonator,
        public readonly Authenticatable $target,
        public readonly string $guard,
        public readonly ?string $reason,
        public readonly array $metadata,
        public readonly string $uuid,
    ) {}
}
