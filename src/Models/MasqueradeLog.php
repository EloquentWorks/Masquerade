<?php

namespace EloquentWorks\Masquerade\Models;

use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $category
 * @property string|null $ability
 * @property string|null $ended_reason
 * @property int $extension_count
 * @property int $risk_score
 * @property string|null $impersonator_type
 * @property int|string|null $impersonator_id
 * @property string|null $target_type
 * @property int|string|null $target_id
 * @property string|null $reason
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $metadata
 * @property array<int, string>|null $risk_flags
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class MasqueradeLog extends Model
{
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
        'risk_flags' => 'array',
        'risk_score' => 'integer',
        'extension_count' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Get the table associated with the model.
     *
     * @return string
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
     * Scope logs for the "Started" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeStarted(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Started);
    }

    /**
     * Scope logs for the "Ended" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeEnded(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Ended);
    }

    /**
     * Scope logs for the "Denied" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeDenied(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Denied);
    }

    /**
     * Scope logs for the "Expired" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Expired);
    }

    /**
     * Scope logs for the "Extended" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExtended(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::Extended);
    }

    /**
     * Scope logs for the "NoteAdded" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNotes(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::NoteAdded);
    }

    /**
     * Scope logs for the "AbilityBlocked" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAbilityBlocked(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::AbilityBlocked);
    }

    /**
     * Scope logs for the "RiskDetected" action.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRiskDetected(Builder $query): Builder
    {
        return $this->scopeForAction($query, MasqueradeAction::RiskDetected);
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
     * Alias for scopeForMasqueradeUuid().
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUuid(Builder $query, string $uuid): Builder
    {
        return $this->scopeForMasqueradeUuid($query, $uuid);
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
     * Scope logs by category.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope logs by blocked ability.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForAbility(Builder $query, string $ability): Builder
    {
        return $query->where('ability', $ability);
    }

    /**
     * Scope high risk logs.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeHighRisk(Builder $query, int $minimumScore = 50): Builder
    {
        return $query->where('risk_score', '>=', $minimumScore);
    }

    /**
     * Determine if the log was written for the given action.
     *
     * @param  MasqueradeAction|string  $action
     * @return bool
     */
    public function isAction(MasqueradeAction|string $action): bool
    {
        return $this->action === ($action instanceof MasqueradeAction ? $action->value : $action);
    }

    /**
     * Calculate the duration between started_at and ended_at.
     *
     * @return int|null The duration in seconds, or null if either timestamp is not set.
     */
    public function durationInSeconds(): ?int
    {
        if (! $this->started_at instanceof Carbon || ! $this->ended_at instanceof Carbon) {
            return null;
        }

        // Calculate the difference in seconds between the started and ended timestamps, ensuring a non-negative result.
        return (int) max(0, $this->started_at->diffInSeconds($this->ended_at, false));
    }

    /**
     * Convert this log into an exportable flat array.
     *
     * @return array<string, mixed>
     */
    public function toExportArray(): array
    {
        return [
            'id' => $this->id,
            'masquerade_uuid' => $this->masquerade_uuid,
            'action' => $this->action,
            'guard' => $this->guard,
            'category' => $this->category,
            'ability' => $this->ability,
            'ended_reason' => $this->ended_reason,
            'extension_count' => $this->extension_count,
            'risk_score' => $this->risk_score,
            'risk_flags' => implode('|', $this->risk_flags ?? []),
            'impersonator_type' => $this->impersonator_type,
            'impersonator_id' => $this->impersonator_id,
            'target_type' => $this->target_type,
            'target_id' => $this->target_id,
            'reason' => $this->reason,
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'metadata' => json_encode($this->metadata ?? [], JSON_THROW_ON_ERROR),
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Determine the morph type for a given Authenticatable model.
     *
     * @param  Authenticatable  $model
     * @return string
     */
    private function morphTypeFor(Authenticatable $model): string
    {
        // If the model is an instance of Eloquent's Model class, use its getMorphClass method to get the morph type.
        // Otherwise, return the class name of the model.
        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        return $model::class;
    }
}
