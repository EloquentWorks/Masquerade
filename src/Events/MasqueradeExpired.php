<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade session expires.
 */
final class MasqueradeExpired
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  mixed  $target
     * @param  string  $guard
     * @param  string  $uuid
     * @return void
     */
    public function __construct(
        public readonly ?Authenticatable $impersonator,
        public readonly mixed $target,
        public readonly string $guard,
        public readonly string $uuid,
    ) {}
}
