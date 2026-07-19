<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event triggered when a masquerade attempt is denied.
 *
 * This event is dispatched when a user attempts to impersonate another user but is denied
 * due to failing the authorization checks defined in the CanMasquerade and CanBeMasqueraded
 * contracts.
 */
final class MasqueradeDenied
{
    /**
     * Create a new event instance.
     *
     * @param  Authenticatable  $impersonator  The user attempting to impersonate another user.
     * @param  Authenticatable  $target  The user being impersonated.
     * @param  string  $guard  The authentication guard being used.
     * @param  string|null  $reason  The reason for denying the masquerade attempt, if any.
     */
    public function __construct(
        public readonly Authenticatable $impersonator,
        public readonly Authenticatable $target,
        public readonly string $guard,
        public readonly ?string $reason,
    ) {}
}
