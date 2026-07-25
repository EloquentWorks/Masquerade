<?php

namespace EloquentWorks\Masquerade\Events;

use EloquentWorks\Masquerade\Models\MasqueradeNote;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Event fired when a masquerade note is added.
 */
final class MasqueradeNoteAdded
{
    /**
     * Create a new instance of the event.
     *
     * @return void
     */
    public function __construct(
        public MasqueradeNote $note,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public string $uuid,
    ) {}
}
