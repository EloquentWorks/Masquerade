<?php

namespace EloquentWorks\Masquerade\Data;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Data Transfer Object (DTO) representing a masquerade session.
 */
final readonly class MasqueradeSession
{
    /**
     * Create a new instance of the MasqueradeSession DTO.
     *
     * @param  bool  $active
     * @param  string|null  $uuid
     * @param  string|null  $guard
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target
     * @param  string|null  $reason
     * @param  array<string, mixed>  $metadata
     * @param  \Carbon\CarbonImmutable|null  $startedAt
     * @param  \Carbon\CarbonImmutable|null  $expiresAt
     * @param  int|null  $elapsedSeconds
     * @param  int|null  $remainingSeconds
     * @param  string|null  $category
     * @param  string|null  $ticketId
     * @param  string|null  $ticketUrl
     * @param  int  $extensionCount
     * @return void
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
        public ?string $category = null,
        public ?string $ticketId = null,
        public ?string $ticketUrl = null,
        public int $extensionCount = 0,
    ) {}

    /**
     * Convert the session DTO into a safe array for views, JSON, and logs.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'active' => $this->active,
            'uuid' => $this->uuid,
            'guard' => $this->guard,
            'impersonator' => $this->impersonator,
            'target' => $this->target,
            'reason' => $this->reason,
            'category' => $this->category,
            'ticket_id' => $this->ticketId,
            'ticket_url' => $this->ticketUrl,
            'metadata' => $this->metadata,
            'started_at' => $this->startedAt,
            'expires_at' => $this->expiresAt,
            'elapsed_seconds' => $this->elapsedSeconds,
            'remaining_seconds' => $this->remainingSeconds,
            'extension_count' => $this->extensionCount,
        ];
    }
}
