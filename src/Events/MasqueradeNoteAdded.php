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
     * @param  \EloquentWorks\Masquerade\Models\MasqueradeNote  $note
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target
     * @param  string  $uuid
     * @return void
     */
    public function __construct(
        public MasqueradeNote $note,
        public ?Authenticatable $impersonator,
        public ?Authenticatable $target,
        public string $uuid,
    ) {}
}
