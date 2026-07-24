<?php

namespace EloquentWorks\Masquerade\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Represents a note associated with a masquerade impersonation session.
 *
 * @property int $id
 * @property string $masquerade_uuid
 * @property string $note
 * @property string|null $author_type
 * @property int|string|null $author_id
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MasqueradeNote extends Model
{
    /**
     * The list of guarded attributes for the model.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        return (string) config('masquerade.notes.table_name', 'masquerade_notes');
    }

    /**
     * Get the author of the note.
     *
     * @return MorphTo<Model, $this>
     */
    public function author(): MorphTo
    {
        return $this->morphTo('author');
    }

    /**
     * Scope a query to only include notes for a given masquerade UUID.
     *
     * @param  Builder<static>  $query
     * @param  string  $uuid
     * @return Builder<static>
     */
    public function scopeForMasqueradeUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('masquerade_uuid', $uuid);
    }
}
