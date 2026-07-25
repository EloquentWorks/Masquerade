<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade session ends.
 */
final class MasqueradeEnded
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(
        public readonly ?Authenticatable $impersonator,
        public readonly mixed $target,
        public readonly string $guard,
        public readonly string $uuid,
    ) {}
}
