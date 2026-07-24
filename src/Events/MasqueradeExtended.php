<?php

namespace EloquentWorks\Masquerade\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade session is extended.
 */
final class MasqueradeExtended
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target
     * @param  string  $guard
     * @param  string  $uuid
     * @param  \Carbon\CarbonImmutable  $previousExpiresAt
     * @param  \Carbon\CarbonImmutable  $expiresAt
     * @param  string|null  $reason
     * @return void
     */
    public function __construct(
        public readonly ?Authenticatable $impersonator,
        public readonly ?Authenticatable $target,
        public readonly string $guard,
        public readonly string $uuid,
        public readonly CarbonImmutable $previousExpiresAt,
        public readonly CarbonImmutable $expiresAt,
        public readonly ?string $reason,
    ) {}
}
