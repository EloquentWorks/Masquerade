<?php

namespace EloquentWorks\Masquerade\Models;

use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
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
 * @property int|string|null $impersonator_id
 * @property string|null $target_type
 * @property int|string|null $target_id
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
     * The attributes that are not mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {
        return (string) config('masquerade.logging.table_name', 'masquerade_logs');
    }

    /**
     * Get the user that started the masquerade session.
     *
     * @return MorphTo<Model, $this>
     */
    public function impersonator(): MorphTo
    {
        return $this->morphTo('impersonator');
    }

    /**
     * Get the user that was impersonated.
     *
     * @return MorphTo<Model, $this>
     */
    public function target(): MorphTo
    {
        return $this->morphTo('target');
    }

    /**
     * Scope logs by action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAction(Builder $query, MasqueradeAction|string $action): Builder
    {
        return $query->where('action', $action instanceof MasqueradeAction ? $action->value : $action);
    }

    /**
     * Scope started logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStarted(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Started);
    }

    /**
     * Scope ended logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEnded(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Ended);
    }

    /**
     * Scope denied logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDenied(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Denied);
    }

    /**
     * Scope expired logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Expired);
    }

    /**
     * Scope extended logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExtended(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Extended);
    }

    /**
     * Scope logs by masquerade UUID.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForMasqueradeUuid(Builder $query, string $uuid): Builder
    {
        return $query->where('masquerade_uuid', $uuid);
    }

    /**
     * Scope logs by impersonator.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForImpersonator(Builder $query, Authenticatable $impersonator): Builder
    {
        return $query
            ->where('impersonator_type', $this->morphTypeFor($impersonator))
            ->where('impersonator_id', $impersonator->getAuthIdentifier());
    }

    /**
     * Scope logs by target.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTarget(Builder $query, Authenticatable $target): Builder
    {
        return $query
            ->where('target_type', $this->morphTypeFor($target))
            ->where('target_id', $target->getAuthIdentifier());
    }

    /**
     * Determine if the log was written for the given action.
     */
    public function isAction(MasqueradeAction|string $action): bool
    {
        return $this->action === ($action instanceof MasqueradeAction ? $action->value : $action);
    }

    /**
     * Calculate the duration between started_at and ended_at.
     */
    public function durationInSeconds(): ?int
    {
        if (! $this->started_at instanceof Carbon || ! $this->ended_at instanceof Carbon) {
            return null;
        }

        return (int) max(0, $this->started_at->diffInSeconds($this->ended_at, false));
    }

    private function morphTypeFor(Authenticatable $model): string
    {
        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        return $model::class;
    }
}
