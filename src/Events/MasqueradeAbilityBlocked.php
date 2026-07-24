<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade ability is blocked.
 */
final class MasqueradeAbilityBlocked
{
    /**
     * Create a new instance of the event.
     *
     * @param  string  $ability
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target
     * @param  string  $uuid
     * @param  string|null  $reason
     * @param  array<string, mixed>  $metadata
     * @return void
     */
    public function __construct(
        public string $ability,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public string $uuid,
        public ?string $reason = null,
        public array $metadata = [],
    ) {}
}
