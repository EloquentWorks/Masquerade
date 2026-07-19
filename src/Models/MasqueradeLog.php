<?php

namespace EloquentWorks\Masquerade\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Class MasqueradeLog
 *
 * Represents a log entry for a masquerade (impersonation) session.
 *
 * @property int $id The unique identifier for the log entry.
 * @property string $uuid The unique identifier for the masquerade session.
 * @property string $impersonator_type The class name of the impersonator model.
 * @property int $impersonator_id The ID of the impersonator model.
 * @property string $target_type The class name of the target model being impersonated.
 * @property int $target_id The ID of the target model being impersonated.
 * @property string|null $guard The authentication guard used for the masquerade session.
 * @property string|null $reason The reason for starting the masquerade session.
 * @property array|null $metadata Additional metadata associated with the masquerade session.
 * @property Carbon|null $started_at The timestamp when the masquerade session started.
 * @property Carbon|null $ended_at The timestamp when the masquerade session ended.
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
     * Get the impersonator model associated with this log entry.
     *
     * @return MorphTo<Model, self> The polymorphic relationship to the impersonator model.
     */
    public function impersonator(): MorphTo
    {
        // The `impersonator` method defines a polymorphic relationship to the impersonator model. It allows you to retrieve the model instance of the user who initiated the masquerade session, regardless of the specific model type (e.g., User, Admin, etc.) that is being impersonated.
        return $this->morphTo();
    }

    /**
     * Get the target model associated with this log entry.
     *
     * @return MorphTo<Model, self> The polymorphic relationship to the target model.
     */
    public function target(): MorphTo
    {
        // The `target` method defines a polymorphic relationship to the target model. It allows you to retrieve the model instance of the user who is being impersonated, regardless of the specific model type (e.g., User, Admin, etc.) that is being impersonated.
        return $this->morphTo();
    }
}
