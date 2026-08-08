<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

final class MasqueradeAbilityBlocked
{
    /**
     * Create a new instance of the event.
     *
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
