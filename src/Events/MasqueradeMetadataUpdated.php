<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

final class MasqueradeMetadataUpdated
{
    /**
     * Create a new instance of the event.
     *
     * @param  array<string, mixed>  $metadata
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
