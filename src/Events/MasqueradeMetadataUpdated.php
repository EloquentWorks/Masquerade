<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when masquerade metadata is updated.
 */
final class MasqueradeMetadataUpdated
{
    /**
     * Create a new instance of the event.
     *
     * @param  array<string, mixed>  $metadata
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target
     * @param  string  $uuid
     * @param  bool  $merged
     * @return void
     */
    public function __construct(
        public array $metadata,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public string $uuid,
        public bool $merged,
    ) {}
}
