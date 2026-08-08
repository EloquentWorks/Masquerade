<?php

namespace EloquentWorks\Masquerade\Events;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

final class MasqueradeExtended
{
    /**
     * Create a new event instance.
     *
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
