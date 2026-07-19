<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade session starts.
 */
final class MasqueradeStarted
{
    /**
     * Create a new event instance.
     *
     * @param  array<string, mixed>  $metadata
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
