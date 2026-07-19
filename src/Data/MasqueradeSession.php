<?php

namespace EloquentWorks\Masquerade\Data;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A data transfer object (DTO) representing a masquerade session.
 */
final readonly class MasqueradeSession
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $active,
        public ?string $uuid,
        public ?string $guard,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public ?string $reason,
        public array $metadata,
        public ?CarbonImmutable $startedAt,
        public ?CarbonImmutable $expiresAt,
        public ?int $elapsedSeconds,
        public ?int $remainingSeconds,
    ) {}

    /**
     * Convert the session DTO into a safe array for views, JSON, and logs.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // We intentionally do not include the impersonator and target user objects in the array representation to avoid exposing sensitive user information.
        return [
            'active' => $this->active,
            'uuid' => $this->uuid,
            'guard' => $this->guard,
            'impersonator' => $this->impersonator,
            'target' => $this->target,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'started_at' => $this->startedAt,
            'expires_at' => $this->expiresAt,
            'elapsed_seconds' => $this->elapsedSeconds,
            'remaining_seconds' => $this->remainingSeconds,
        ];
    }
}
