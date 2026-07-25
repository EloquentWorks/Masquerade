<?php

namespace EloquentWorks\Masquerade\Events;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade risk is detected.
 */
final class MasqueradeRiskDetected
{
    /**
     * Create a new instance of the event.
     *
     * @param  array<int, string>  $flags
     * @param  array<string, mixed>  $metadata
     * @return void
     */
    public function __construct(
        public int $score,
        public array $flags,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public string $uuid,
        public string $trigger,
        public array $metadata = [],
    ) {}
}
