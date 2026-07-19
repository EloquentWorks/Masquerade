<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when masquerading ends.
 */
final class MasqueradeExpired
{
    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly ?Authenticatable $impersonator,
        public readonly mixed $target,
        public readonly string $guard,
        public readonly string $uuid,
    ) {}
}
