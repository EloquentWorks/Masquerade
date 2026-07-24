<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade attempt is denied.
 */
final class MasqueradeDenied
{
    /**
     * Create a new event instance.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $target
     * @param  string  $guard
     * @param  string|null  $reason
     * @param  array<string, mixed>  $metadata
     * @param  string|null  $uuid
     * @return void
     */
    public function __construct(
        public readonly Authenticatable $impersonator,
        public readonly Authenticatable $target,
        public readonly string $guard,
        public readonly ?string $reason,
        public readonly array $metadata = [],
        public readonly ?string $uuid = null,
    ) {}
}
