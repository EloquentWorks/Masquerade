<?php

namespace EloquentWorks\Masquerade\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Represents a log entry for a masquerade impersonation session.
 *
 * @property int $id
 * @property string $masquerade_uuid
 * @property string $action
 * @property string|null $guard
 * @property string|null $impersonator_type
 * @property int|null $impersonator_id
 * @property string|null $target_type
 * @property int|null $target_id
 * @property string|null $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MasqueradeLog extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the table name for the MasqueradeLog model.
     *
     * @return string The table name for the MasqueradeLog model.
     */
    public function getTable(): string
    {
        // The `getTable` method retrieves the table name for the MasqueradeLog model. It checks the configuration for a custom table name under the key 'masquerade.logging.table_name'. If no custom table name is set, it defaults to 'masquerade_logs'.
        return (string) config('masquerade.logging.table_name', 'masquerade_logs');
    }

    /**
     * Get the user that started the masquerade session.
     *
     * @return MorphTo<Model, $this>
     */
    public function impersonator(): MorphTo
    {
        // The `impersonator` method defines a polymorphic relationship to the user model that started the masquerade session. It uses the `morphTo` method to allow for different user models to be associated with the masquerade log entry. The relationship is defined by the `impersonator_type` and `impersonator_id` columns in the database.
        return $this->morphTo('impersonator');
    }

    /**
     * Get the user that was impersonated.
     *
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        // The `target` method defines a polymorphic relationship to the user model that was impersonated during the masquerade session. It uses the `morphTo` method to allow for different user models to be associated with the masquerade log entry. The relationship is defined by the `target_type` and `target_id` columns in the database.
        return $this->morphTo('target');
    }
}
