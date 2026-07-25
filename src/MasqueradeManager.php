<?php

namespace EloquentWorks\Masquerade;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Data\MasqueradeSession;
use EloquentWorks\Masquerade\Enums\Masquerade;
use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Events\MasqueradeAbilityBlocked;
use EloquentWorks\Masquerade\Events\MasqueradeDenied;
use EloquentWorks\Masquerade\Events\MasqueradeEnded;
use EloquentWorks\Masquerade\Events\MasqueradeExpired;
use EloquentWorks\Masquerade\Events\MasqueradeExtended;
use EloquentWorks\Masquerade\Events\MasqueradeMetadataUpdated;
use EloquentWorks\Masquerade\Events\MasqueradeNoteAdded;
use EloquentWorks\Masquerade\Events\MasqueradeRiskDetected;
use EloquentWorks\Masquerade\Events\MasqueradeStarted;
use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Exceptions\MasqueradeAbilityBlockedException;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Models\MasqueradeNote;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Class MasqueradeManager
 */
final class MasqueradeManager
{
    /**
     * MasqueradeManager constructor.
     */
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly Session $session,
        private readonly Dispatcher $events,
        private readonly Request $request,
    ) {}

    /**
     * Start masquerading as the target user.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws CannotMasqueradeException
     */
    public function start(
        Authenticatable $target,
        ?Authenticatable $impersonator = null,
        ?string $guard = null,
        ?string $reason = null,
        array $metadata = [],
        ?string $category = null,
    ): void {
        // Resolve the guard and impersonator
        $guard = $this->resolveGuard($guard);
        $impersonator ??= $this->auth->guard($guard)->user();
        $category = $this->resolveCategory($category);

        // Validate the impersonator
        if (! $impersonator instanceof Authenticatable) {
            throw CannotMasqueradeException::because('No authenticated impersonator was found.');
        }

        // Validate the target
        if ($this->isMasquerading() && ! (bool) config('masquerade.security.allow_nested', false)) {
            throw CannotMasqueradeException::because('Nested masquerade sessions are disabled.');
        }

        // Validate the reason if required
        if ((bool) config('masquerade.security.require_reason', false) && blank($reason)) {
            throw CannotMasqueradeException::because('A reason is required before starting a masquerade session.');
        }

        // Validate the category if allowed categories are defined
        $this->validateCategory($category);

        // Check if the impersonator is allowed to masquerade as the target
        if (! $this->canMasquerade($impersonator, $target)) {
            // Record the denied attempt and dispatch the event
            $uuid = (string) Str::uuid();

            // Merge the metadata with additional information about the denied attempt
            $this->recordDeniedAttempt($impersonator, $target, $guard, $reason, $metadata, $uuid, $category);
            $this->events->dispatch(new MasqueradeDenied($impersonator, $target, $guard, $reason, $metadata, $uuid));

            // Detect risk for the denied attempt
            $this->detectRisk($impersonator, $target, $uuid, 'denied');

            // Throw an exception with a configurable message
            throw CannotMasqueradeException::because((string) config('masquerade.messages.denied', 'You are not allowed to masquerade as this user.'));
        }

        // Start the masquerade session
        $now = CarbonImmutable::now();
        $uuid = (string) Str::uuid();
        $minutes = max(1, (int) config('masquerade.duration.minutes', 60));

        // Store the masquerade session data in the session
        $this->session->put($this->key('active'), true);
        $this->session->put($this->key('uuid'), $uuid);
        $this->session->put($this->key('guard'), $guard);
        $this->session->put($this->key('impersonator_id'), $impersonator->getAuthIdentifier());
        $this->session->put($this->key('impersonator_type'), $impersonator::class);
        $this->session->put($this->key('target_id'), $target->getAuthIdentifier());
        $this->session->put($this->key('target_type'), $target::class);
        $this->session->put($this->key('reason'), $reason);
        $this->session->put($this->key('category'), $category);
        $this->session->put($this->key('metadata'), $metadata);
        $this->session->put($this->key('started_at'), $now->toIso8601String());
        $this->session->put($this->key('expires_at'), $now->addMinutes($minutes)->toIso8601String());
        $this->session->put($this->key('extension_count'), 0);

        // Record the masquerade start action
        $this->record(
            action: MasqueradeAction::Started,
            impersonator: $impersonator,
            target: $target,
            guard: $guard,
            reason: $reason,
            metadata: $metadata,
            startedAt: $now,
            endedAt: null,
            uuid: $uuid,
            category: $category,
        );

        // Log the masquerade start action
        $this->auth->guard($guard)->login($target, false);

        // Regenerate the session ID if configured to do so
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch the MasqueradeStarted event
        $this->events->dispatch(new MasqueradeStarted($impersonator, $target, $guard, $reason, $metadata, $uuid));

        // Detect risk for the masquerade start action
        $this->detectRisk($impersonator, $target, $uuid, 'started');
    }

    /**
     * Stop masquerading and return to the original user.
     *
     * @return void Returns nothing.
     */
    public function stop(?string $guard = null, bool $expired = false, ?string $endedReason = null): void
    {
        // If not currently masquerading, do nothing
        if (! $this->isMasquerading()) {
            return;
        }

        // Resolve the guard, UUID, reason, metadata, startedAt, category, and extensionCount
        $guard = $this->resolveGuard($guard ?? $this->session->get($this->key('guard')));
        $uuid = $this->uuid() ?? (string) Str::uuid();
        $reason = $this->reason();
        $metadata = $this->metadata();
        $startedAt = $this->startedAt();
        $category = $this->category();
        $extensionCount = $this->extensionCount();

        // Get the impersonator and target models
        $impersonator = $this->impersonator();
        $target = $this->target() ?? $this->auth->guard($guard)->user();

        // Log the masquerade end action and return to the original user
        if ($impersonator instanceof Authenticatable) {
            $this->auth->guard($guard)->login($impersonator, false);
        } elseif ((bool) config('masquerade.security.logout_on_missing_original_user', true)) {
            $this->auth->guard($guard)->logout();
        }

        // Record the masquerade end or expired action
        $this->record(
            action: $expired ? MasqueradeAction::Expired : MasqueradeAction::Ended,
            impersonator: $impersonator,
            target: $target instanceof Authenticatable ? $target : null,
            guard: $guard,
            reason: $reason,
            metadata: $metadata,
            startedAt: $startedAt,
            endedAt: CarbonImmutable::now(),
            uuid: $uuid,
            category: $category,
            endedReason: $endedReason ?? ($expired ? 'expired' : 'manual'),
            extensionCount: $extensionCount,
        );

        // Clear the masquerade session data from the session
        $this->clear();

        // Regenerate the session ID if configured to do so
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch the appropriate event based on whether the session expired or ended manually
        if ($expired) {
            $this->events->dispatch(new MasqueradeExpired($impersonator, $target, $guard, $uuid));

            return;
        }

        // Dispatch the MasqueradeEnded event
        $this->events->dispatch(new MasqueradeEnded($impersonator, $target, $guard, $uuid));
    }

    /**
     * Extend the current masquerade session by a number of minutes.
     *
     * @throws CannotMasqueradeException
     */
    public function extend(int $minutes, ?string $reason = null): CarbonImmutable
    {
        // Ensure that there is an active masquerade session
        if (! $this->isMasquerading()) {
            throw CannotMasqueradeException::because('No active masquerade session was found.');
        }

        // Ensure that masquerade session extension is allowed
        if (! (bool) config('masquerade.duration.allow_extension', true) || ! (bool) config('masquerade.extensions.enabled', true)) {
            throw CannotMasqueradeException::because('Masquerade session extension is disabled.');
        }

        // Ensure that a reason is provided if required
        if ((bool) config('masquerade.extensions.require_reason', false) && blank($reason)) {
            throw CannotMasqueradeException::because('A reason is required before extending a masquerade session.');
        }

        // Ensure that the maximum number of extensions has not been reached
        $extensionCount = $this->extensionCount();
        $maxExtensions = (int) config('masquerade.extensions.max_extensions', 0);

        // If the maximum number of extensions is greater than 0 and the current extension count is greater than or equal to the maximum, throw an exception
        if ($maxExtensions > 0 && $extensionCount >= $maxExtensions) {
            throw CannotMasqueradeException::because('The maximum number of masquerade session extensions has been reached.');
        }

        // Ensure that the number of minutes to extend is at least 1 and does not exceed the maximum allowed per extension
        $maxMinutesPerExtension = (int) config('masquerade.extensions.max_minutes_per_extension', 0);
        $minutes = max(1, $minutes);

        // If the maximum number of minutes per extension is greater than 0, limit the number of minutes to extend to that value
        if ($maxMinutesPerExtension > 0) {
            $minutes = min($minutes, $maxMinutesPerExtension);
        }

        // Calculate the new expiration time based on the previous expiration time and the number of minutes to extend
        $previousExpiresAt = $this->expiresAt() ?? CarbonImmutable::now();
        $expiresAt = $previousExpiresAt->addMinutes($minutes);
        $startedAt = $this->startedAt();
        $maxMinutes = (int) config('masquerade.duration.max_minutes', 0);

        // If the maximum number of minutes for the entire masquerade session is greater than 0, limit the new expiration time to that value
        if ($startedAt instanceof CarbonImmutable && $maxMinutes > 0) {
            $maximumExpiresAt = $startedAt->addMinutes($maxMinutes);

            // If the new expiration time is greater than the maximum allowed expiration time, set the new expiration time to the maximum allowed expiration time
            if ($expiresAt->greaterThan($maximumExpiresAt)) {
                $expiresAt = $maximumExpiresAt;
            }
        }

        // Update the session with the new expiration time and increment the extension count
        $newExtensionCount = $extensionCount + 1;
        $this->session->put($this->key('expires_at'), $expiresAt->toIso8601String());
        $this->session->put($this->key('extension_count'), $newExtensionCount);

        // Merge the existing metadata with the new extension information
        $metadata = array_merge($this->metadata(), [
            'extended_by_minutes' => $minutes,
            'previous_expires_at' => $previousExpiresAt->toIso8601String(),
            'new_expires_at' => $expiresAt->toIso8601String(),
            'extension_count' => $newExtensionCount,
        ]);

        // Record the masquerade extension action
        $this->record(
            action: MasqueradeAction::Extended,
            impersonator: $this->impersonator(),
            target: $this->target(),
            guard: $this->guard() ?? $this->resolveGuard(null),
            reason: $reason ?? $this->reason(),
            metadata: $metadata,
            startedAt: $startedAt,
            endedAt: null,
            uuid: $this->uuid() ?? (string) Str::uuid(),
            category: $this->category(),
            extensionCount: $newExtensionCount,
        );

        // Dispatch the MasqueradeExtended event with the relevant information
        $this->events->dispatch(new MasqueradeExtended(
            impersonator: $this->impersonator(),
            target: $this->target(),
            guard: $this->guard() ?? $this->resolveGuard(null),
            uuid: $this->uuid() ?? '',
            previousExpiresAt: $previousExpiresAt,
            expiresAt: $expiresAt,
            reason: $reason,
        ));

        // Detect risk for the masquerade extension action
        return $expiresAt;
    }

    /**
     * Merge or replace metadata on the active masquerade session.
     *
     * @param  array<string, mixed>  $metadata
     * @param  bool  $merge  Whether to merge the new metadata with the existing metadata (default: true).
     *                       If false, the new metadata will replace the existing metadata.
     *
     * @throws CannotMasqueradeException
     */
    public function updateMetadata(array $metadata, bool $merge = true): void
    {
        // Ensure that there is an active masquerade session
        if (! $this->isMasquerading()) {
            throw CannotMasqueradeException::because('No active masquerade session was found.');
        }

        // Merge or replace the existing metadata with the new metadata based on the $merge parameter
        $updated = $merge ? array_merge($this->metadata(), $metadata) : $metadata;

        // Update the session with the new metadata
        $this->session->put($this->key('metadata'), $updated);

        // Record the masquerade metadata update action
        $this->record(
            action: MasqueradeAction::MetadataUpdated,
            impersonator: $this->impersonator(),
            target: $this->target(),
            guard: $this->guard() ?? $this->resolveGuard(null),
            reason: $this->reason(),
            metadata: $updated,
            startedAt: $this->startedAt(),
            endedAt: null,
            uuid: $this->uuid() ?? (string) Str::uuid(),
            category: $this->category(),
            extensionCount: $this->extensionCount(),
        );

        // Dispatch the MasqueradeMetadataUpdated event with the relevant information
        $this->events->dispatch(new MasqueradeMetadataUpdated(
            metadata: $updated,
            impersonator: $this->impersonator(),
            target: $this->target(),
            uuid: $this->uuid() ?? '',
            merged: $merge,
        ));
    }

    /**
     * Add a note to the active masquerade session.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function addNote(string $note, ?Authenticatable $author = null, array $metadata = []): MasqueradeNote
    {
        // Ensure that there is an active masquerade session
        if (! $this->isMasquerading()) {
            throw CannotMasqueradeException::because('No active masquerade session was found.');
        }

        // Ensure that masquerade notes are enabled in the configuration
        if (! (bool) config('masquerade.notes.enabled', true)) {
            throw CannotMasqueradeException::because('Masquerade notes are disabled.');
        }

        // Generate a UUID for the note and determine the author (defaulting to the impersonator if not provided)
        $uuid = $this->uuid() ?? (string) Str::uuid();
        $author ??= $this->impersonator();

        // Determine the model class for masquerade notes from the configuration, defaulting
        // to MasqueradeNote if not specified or invalid
        $modelClass = config('masquerade.notes.model', MasqueradeNote::class);

        // Validate that the model class is a string and exists, defaulting to MasqueradeNote if not
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $modelClass = MasqueradeNote::class;
        }

        /** @var class-string<MasqueradeNote> $modelClass */
        $created = $modelClass::query()->create([
            'masquerade_uuid' => $uuid,
            'note' => $note,
            'author_type' => $this->morphTypeFor($author),
            'author_id' => $author?->getAuthIdentifier(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        // Record the masquerade note addition action
        $this->record(
            action: MasqueradeAction::NoteAdded,
            impersonator: $this->impersonator(),
            target: $this->target(),
            guard: $this->guard() ?? $this->resolveGuard(null),
            reason: $this->reason(),
            metadata: array_merge($metadata, ['note_id' => $created->getKey()]),
            startedAt: $this->startedAt(),
            endedAt: null,
            uuid: $uuid,
            category: $this->category(),
            extensionCount: $this->extensionCount(),
        );

        // Dispatch the MasqueradeNoteAdded event with the relevant information
        $this->events->dispatch(new MasqueradeNoteAdded($created, $this->impersonator(), $this->target(), $uuid));

        // Return the created MasqueradeNote instance
        return $created;
    }

    /**
     * Get all notes for the active masquerade session.
     *
     * @return EloquentCollection<int, MasqueradeNote>
     */
    public function notes(?string $uuid = null): EloquentCollection
    {
        // Ensure that there is an active masquerade session
        $uuid ??= $this->uuid() ?? '';

        /** @var EloquentCollection<int, MasqueradeNote> $notes */
        $notes = MasqueradeNote::query()
            ->forMasqueradeUuid($uuid)
            ->oldest()
            ->get();

        // Return the collection of MasqueradeNote instances for the specified UUID
        return $notes;
    }

    /**
     * Determine if the current masquerade session blocks a given ability.
     */
    public function blocksAbility(string $ability): bool
    {
        // If not currently masquerading, the ability is not blocked
        if (! $this->isMasquerading()) {
            return false;
        }

        // Retrieve the list of blocked abilities from the configuration
        $blocked = config('masquerade.abilities.blocked', []);

        // Check if the given ability is in the list of blocked abilities, using strict comparison
        return is_array($blocked) && in_array($ability, array_map('strval', $blocked), true);
    }

    /**
     * Assert that the current masquerade session allows a given ability.
     *
     * @throws MasqueradeAbilityBlockedException
     */
    public function assertAbilityAllowed(string $ability): void
    {
        // If the ability is not blocked, do nothing
        if (! $this->blocksAbility($ability)) {
            return;
        }

        // Record the blocked ability and throw an exception indicating that the ability is blocked
        $this->recordBlockedAbility($ability);

        // Throw an exception indicating that the ability is blocked during masquerade
        throw MasqueradeAbilityBlockedException::forAbility($ability);
    }

    /**
     * Record a blocked ability during the current masquerade session.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordBlockedAbility(string $ability, ?string $reason = null, array $metadata = []): void
    {
        // If not currently masquerading, do nothing
        if (! $this->isMasquerading()) {
            return;
        }

        // Generate a UUID for the blocked ability record, using the existing UUID if available
        $uuid = $this->uuid() ?? (string) Str::uuid();

        // Record the blocked ability action in the masquerade log if logging is enabled in the configuration
        if ((bool) config('masquerade.abilities.log_blocked', true)) {
            $this->record(
                action: MasqueradeAction::AbilityBlocked,
                impersonator: $this->impersonator(),
                target: $this->target(),
                guard: $this->guard() ?? $this->resolveGuard(null),
                reason: $reason ?? $this->reason(),
                metadata: $metadata,
                startedAt: $this->startedAt(),
                endedAt: null,
                uuid: $uuid,
                category: $this->category(),
                ability: $ability,
                extensionCount: $this->extensionCount(),
            );
        }

        // Dispatch the MasqueradeAbilityBlocked event with the relevant information
        $this->events->dispatch(new MasqueradeAbilityBlocked(
            ability: $ability,
            impersonator: $this->impersonator(),
            target: $this->target(),
            uuid: $uuid,
            reason: $reason,
            metadata: $metadata,
        ));

        // Detect risk for the blocked ability action
        $this->detectRisk($this->impersonator(), $this->target(), $uuid, 'ability_blocked');
    }

    /**
     * Determine if the current session is masquerading.
     */
    public function isMasquerading(): bool
    {
        // Check if the 'active' key in the session is set to true, indicating that a masquerade session is active
        return (bool) $this->session->get($this->key('active'), false);
    }

    /**
     * Determine if the current masquerade session has expired.
     */
    public function hasExpired(): bool
    {
        // If not currently masquerading, the session cannot be expired
        if (! $this->isMasquerading()) {
            return false;
        }

        // If masquerade duration is disabled in the configuration, the session cannot be expired
        if (! (bool) config('masquerade.duration.enabled', true)) {
            return false;
        }

        // Get the expiration time of the current masquerade session
        $expiresAt = $this->expiresAt();

        // Check if the expiration time is a valid CarbonImmutable instance and if
        // it is in the past, indicating that the session has expired
        return $expiresAt instanceof CarbonImmutable && $expiresAt->isPast();
    }

    /**
     * Stop the session if it expired.
     */
    public function stopIfExpired(): bool
    {
        // If not currently masquerading, do nothing and return false
        if (! $this->hasExpired()) {
            return false;
        }

        // Stop the masquerade session and mark it as expired
        $this->stop(expired: true);

        // Return true to indicate that the session was stopped due to expiration
        return true;
    }

    /**
     * Determine if an impersonator may masquerade as a target.
     */
    public function canMasquerade(Authenticatable $impersonator, Authenticatable $target): bool
    {
        // Check if the configuration allows the same user to masquerade as themselves
        if (! (bool) config('masquerade.security.allow_same_user', false)) {
            if ($impersonator::class === $target::class && $impersonator->getAuthIdentifier() === $target->getAuthIdentifier()) {
                return false;
            }
        }

        // Check if model methods should be used for permission checks
        if (! (bool) config('masquerade.permissions.use_model_methods', true)) {
            return true;
        }

        // Get the method names for impersonator and target permission checks from the configuration
        $impersonatorMethod = (string) config('masquerade.permissions.impersonator_method', 'canMasquerade');
        $targetMethod = (string) config('masquerade.permissions.target_method', 'canBeMasqueradedBy');

        // Check if the impersonator has the method and if it returns false when called with the target
        if (method_exists($impersonator, $impersonatorMethod) && $impersonator->{$impersonatorMethod}($target) === false) {
            return false;
        }

        // Check if the target has the method and if it returns false when called with the impersonator
        if (method_exists($target, $targetMethod) && $target->{$targetMethod}($impersonator) === false) {
            return false;
        }

        // If none of the checks failed, return true to indicate that the impersonator may masquerade as the target
        return true;
    }

    /**
     * Determine if the current session is masquerading as the given user.
     */
    public function isMasqueradingAs(Authenticatable $target): bool
    {
        // If not currently masquerading, return false
        $currentTarget = $this->target();

        // Check if the current target is an instance of Authenticatable and if it
        // matches the given target by class and identifier
        return $currentTarget instanceof Authenticatable
            && $currentTarget::class === $target::class
            && $currentTarget->getAuthIdentifier() === $target->getAuthIdentifier();
    }

    /**
     * Determine if the current session was started by the given user.
     */
    public function isMasqueradedBy(Authenticatable $impersonator): bool
    {
        // If not currently masquerading, return false
        $currentImpersonator = $this->impersonator();

        // Check if the current impersonator is an instance of Authenticatable and if it
        // matches the given impersonator by class and identifier
        return $currentImpersonator instanceof Authenticatable
            && $currentImpersonator::class === $impersonator::class
            && $currentImpersonator->getAuthIdentifier() === $impersonator->getAuthIdentifier();
    }

    /**
     * Get the original impersonator model.
     */
    public function impersonator(): ?Authenticatable
    {
        // Resolve and return the impersonator model based on the stored impersonator type and ID in the session
        return $this->resolveAuthenticatable(
            $this->session->get($this->key('impersonator_type')),
            $this->session->get($this->key('impersonator_id')),
        );
    }

    /**
     * Get the target model being masqueraded as.
     */
    public function target(): ?Authenticatable
    {
        // Resolve and return the target model based on the stored target type and ID in the session
        return $this->resolveAuthenticatable(
            $this->session->get($this->key('target_type')),
            $this->session->get($this->key('target_id')),
        );
    }

    /**
     * Get the UUID of the current masquerade session.
     */
    public function uuid(): ?string
    {
        // Get the UUID from the masquerade session metadata using the session key
        $uuid = $this->session->get($this->key('uuid'));

        // Return the UUID as a string if it is a string, otherwise return null
        return is_string($uuid) ? $uuid : null;
    }

    /**
     * Get the guard of the current masquerade session.
     */
    public function guard(): ?string
    {
        // Get the guard from the masquerade session metadata using the session key
        $guard = $this->session->get($this->key('guard'));

        // Return the guard as a string if it is a string, otherwise return null
        return is_string($guard) ? $guard : null;
    }

    /**
     * Get the reason for the current masquerade session.
     */
    public function reason(): ?string
    {
        // Get the reason from the masquerade session metadata using the session key
        $reason = $this->session->get($this->key('reason'));

        // Return the reason as a string if it is a string, otherwise return null
        return is_string($reason) ? $reason : null;
    }

    /**
     * Get the category of the current masquerade session.
     */
    public function category(): ?string
    {
        // Get the category from the masquerade session metadata using the session key
        $category = $this->session->get($this->key('category'));

        // Return the category as a string if it is a string, otherwise return null
        return is_string($category) ? $category : null;
    }

    /**
     * Get the number of times the current masquerade session has been extended.
     */
    public function extensionCount(): int
    {
        // Get the extension count from the masquerade session metadata using the session key, defaulting to 0 if not set
        return max(0, (int) $this->session->get($this->key('extension_count'), 0));
    }

    /**
     * Get the ticket ID of the current masquerade session.
     */
    public function ticketId(): ?string
    {
        // Get the ticket ID from the masquerade session metadata using the contextValue method
        $value = $this->contextValue('ticket_id') ?? $this->contextValue('ticket');

        // Return the ticket ID as a string if it is a scalar value, otherwise return null
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Get the ticket URL of the current masquerade session.
     */
    public function ticketUrl(): ?string
    {
        // Get the ticket URL from the masquerade session metadata using the contextValue method
        $value = $this->contextValue('ticket_url');

        // Return the ticket URL as a string if it is a scalar value, otherwise return null
        return is_scalar($value) ? (string) $value : null;
    }

    /**
     * Get a value from the masquerade session metadata.
     */
    public function contextValue(string $key, mixed $default = null): mixed
    {
        // Use the data_get helper to retrieve a value from the metadata array using
        // dot notation, returning the default value if the key does not exist
        return data_get($this->metadata(), $key, $default);
    }

    /**
     * Get the metadata of the current masquerade session.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        // Retrieve the metadata from the session and ensure it is an array
        $metadata = $this->session->get($this->key('metadata'), []);

        // Return the metadata as an array, defaulting to an empty array if it is not an array
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Get the start time of the current masquerade session.
     */
    public function startedAt(): ?CarbonImmutable
    {
        // Parse the start time from the session and return it as a CarbonImmutable instance, or null if it cannot be parsed
        return $this->parseTime($this->session->get($this->key('started_at')));
    }

    /**
     * Get the expiration time of the current masquerade session.
     */
    public function expiresAt(): ?CarbonImmutable
    {
        // Parse the expiration time from the session and return it as a CarbonImmutable
        // instance, or null if it cannot be parsed
        return $this->parseTime($this->session->get($this->key('expires_at')));
    }

    /**
     * Get the number of seconds that have elapsed since the start of the current masquerade session.
     */
    public function elapsedSeconds(): ?int
    {
        // Get the start time of the current masquerade session
        $startedAt = $this->startedAt();

        // If the start time is not a valid CarbonImmutable instance, return null
        if (! $startedAt instanceof CarbonImmutable) {
            return null;
        }

        // Calculate the number of seconds that have elapsed since the start time and return it, ensuring it is not negative
        return (int) max(0, $startedAt->diffInSeconds(CarbonImmutable::now(), false));
    }

    /**
     * Get the number of seconds remaining until the expiration of the current masquerade session.
     */
    public function remainingSeconds(): ?int
    {
        // Get the expiration time of the current masquerade session
        $expiresAt = $this->expiresAt();

        // If the expiration time is not a valid CarbonImmutable instance, return null
        if (! $expiresAt instanceof CarbonImmutable) {
            return null;
        }

        // Calculate the number of seconds remaining until the expiration time and return it, ensuring it is not negative
        return (int) max(0, CarbonImmutable::now()->diffInSeconds($expiresAt, false));
    }

    /**
     * Get a typed snapshot of the current masquerade session.
     *
     * @return Models\MasqueradeSession|null
     */
    public function session(): ?MasqueradeSession
    {
        // If not currently masquerading, return null
        if (! $this->isMasquerading()) {
            return null;
        }

        // Create and return a new MasqueradeSession instance with the current session data
        return new MasqueradeSession(
            active: true,
            uuid: $this->uuid(),
            guard: $this->guard(),
            impersonator: $this->impersonator(),
            target: $this->target(),
            reason: $this->reason(),
            metadata: $this->metadata(),
            startedAt: $this->startedAt(),
            expiresAt: $this->expiresAt(),
            elapsedSeconds: $this->elapsedSeconds(),
            remainingSeconds: $this->remainingSeconds(),
            category: $this->category(),
            ticketId: $this->ticketId(),
            ticketUrl: $this->ticketUrl(),
            extensionCount: $this->extensionCount(),
        );
    }

    /**
     * Return a small context array that is safe for UI display.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        // Get the current masquerade session
        $session = $this->session();

        // If the session is not a valid MasqueradeSession instance, return a default context array with null values
        if (! $session instanceof MasqueradeSession) {
            return [
                'active' => false,
                'uuid' => null,
                'guard' => null,
                'impersonator' => null,
                'target' => null,
                'reason' => null,
                'category' => null,
                'ticket_id' => null,
                'ticket_url' => null,
                'metadata' => [],
                'started_at' => null,
                'expires_at' => null,
                'elapsed_seconds' => null,
                'remaining_seconds' => null,
                'extension_count' => 0,
            ];
        }

        // Return the session data as an array for UI display
        return $session->toArray();
    }

    /**
     * Clear all masquerade session keys.
     */
    public function clear(): void
    {
        // Clear all masquerade session keys by forgetting the base key in the session
        $this->session->forget($this->baseKey());
    }

    /**
     * Resolve the guard to use for the current masquerade session.
     */
    private function resolveGuard(?string $guard): string
    {
        // If the guard is not provided, attempt to resolve it from the configuration
        $guard ??= config('masquerade.guard');
        $guard ??= config('auth.defaults.guard', 'web');

        // If the guard is still not a valid string, default to 'web'
        return (string) $guard;
    }

    /**
     * Resolve the category to use for the current masquerade session.
     */
    private function resolveCategory(?string $category): ?string
    {
        // If the category is not provided, attempt to resolve it from the configuration
        $category ??= config('masquerade.reasons.default_category');

        // If the category is not a valid string or is an empty string, return null
        return is_string($category) && $category !== '' ? $category : null;
    }

    /**
     * Validate the category for the current masquerade session.
     *
     * @throws CannotMasqueradeException
     */
    private function validateCategory(?string $category): void
    {
        // If the category is null, return without validation
        if ($category === null) {
            return;
        }

        // Get the list of allowed categories from the configuration
        $allowed = config('masquerade.reasons.allowed_categories', []);

        // If the allowed categories are not an array or are empty, return without validation
        if (! is_array($allowed) || $allowed === []) {
            return;
        }

        // Check if the provided category is in the list of allowed categories, using strict comparison
        if (! in_array($category, array_map('strval', $allowed), true)) {
            throw CannotMasqueradeException::because("The masquerade category [{$category}] is not allowed.");
        }
    }

    /**
     * Get the base session key for masquerade.
     */
    private function baseKey(): string
    {
        // Get the base session key for masquerade from the configuration, defaulting to 'masquerade' if not set
        return (string) config('masquerade.session_key', 'masquerade');
    }

    /**
     * Get the full session key for a given masquerade attribute.
     */
    private function key(string $name): string
    {
        // Concatenate the base session key with the given attribute name to form the full session key
        return $this->baseKey().'.'.$name;
    }

    /**
     * Parse a time value into a CarbonImmutable instance.
     */
    private function parseTime(mixed $value): ?CarbonImmutable
    {
        // If the value is not a string or is an empty string, return null
        if (! is_string($value) || $value === '') {
            return null;
        }

        // Attempt to parse the value into a CarbonImmutable instance, returning null if parsing fails
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve an Authenticatable model from a given type and ID.
     */
    private function resolveAuthenticatable(mixed $type, mixed $id): ?Authenticatable
    {
        // If the type is not a string, is an empty string, or the ID is null, return null
        if (! is_string($type) || $type === '' || $id === null) {
            return null;
        }

        // Check if the type is a valid class that exists and is a subclass of the Eloquent Model class, returning null if not
        if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $type */
        $model = $type::query()->find($id);

        // Return the model if it is an instance of Authenticatable, otherwise return null
        return $model instanceof Authenticatable ? $model : null;
    }

    /**
     * Record a denied masquerade attempt in the log.
     *
     * @param  array<string, mixed>  $metadata
     */
    private function recordDeniedAttempt(
        Authenticatable $impersonator,
        Authenticatable $target,
        string $guard,
        ?string $reason,
        array $metadata,
        string $uuid,
        ?string $category,
    ): void {
        // If logging of denied attempts is disabled in the configuration, return without recording
        if (! (bool) config('masquerade.logging.log_denied_attempts', true)) {
            return;
        }

        // Record the denied masquerade attempt in the log with the provided details, including the impersonator,
        // target, guard, reason, metadata, UUID, and category
        $this->record(
            MasqueradeAction::Denied,
            $impersonator,
            $target,
            $guard,
            $reason,
            $metadata,
            CarbonImmutable::now(),
            CarbonImmutable::now(),
            $uuid,
            category: $category
        );
    }

    /**
     * Get the morph type for a given Authenticatable model.
     */
    private function morphTypeFor(?Authenticatable $model): ?string
    {
        // If the model is not an instance of Authenticatable or its identifier is null, return null
        if (! $model instanceof Authenticatable || $model->getAuthIdentifier() === null) {
            return null;
        }

        // If the model is an instance of Eloquent Model, return its morph class, otherwise return its class name
        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        // If the model is not an Eloquent Model, return its class name
        return $model::class;
    }

    /**
     * Detect and handle potential risks associated with masquerade actions.
     */
    private function detectRisk(?Authenticatable $impersonator, ?Authenticatable $target, string $uuid, string $trigger): void
    {
        // If risk detection is disabled in the configuration or the impersonator is not an instance of
        // Authenticatable, return without detecting risk
        if (! (bool) config('masquerade.risk.enabled', false) || ! $impersonator instanceof Authenticatable) {
            return;
        }

        // Get the model class for masquerade logs from the configuration, defaulting to MasqueradeLog if not specified or invalid
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Validate that the model class is a string and exists, returning without detecting risk if not
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $since = CarbonImmutable::now()->subHour();
        $flags = [];
        $score = 0;

        // Check for too many sessions started by the impersonator within the last hour
        $startedLimit = (int) config('masquerade.risk.max_sessions_per_hour', 0);
        if ($startedLimit > 0) {
            $startedCount = (int) $modelClass::query()
                ->started()
                ->forImpersonator($impersonator)
                ->where('created_at', '>=', $since)
                ->count();

            // If the count of started sessions exceeds the limit, add a flag and increase the risk score
            if ($startedCount > $startedLimit) {
                $flags[] = 'too_many_sessions';
                $score += 40;
            }
        }

        // Check for too many denied attempts by the impersonator within the last hour
        $deniedLimit = (int) config('masquerade.risk.max_denied_attempts_per_hour', 0);
        if ($deniedLimit > 0) {
            $deniedCount = (int) $modelClass::query()
                ->denied()
                ->forImpersonator($impersonator)
                ->where('created_at', '>=', $since)
                ->count();

            // If the count of denied attempts exceeds the limit, add a flag and increase the risk score
            if ($deniedCount > $deniedLimit) {
                $flags[] = 'too_many_denied_attempts';
                $score += 35;
            }
        }

        // Check for too many blocked abilities by the impersonator within the last hour
        $blockedLimit = (int) config('masquerade.risk.max_blocked_abilities_per_hour', 0);
        if ($blockedLimit > 0) {
            $blockedCount = (int) $modelClass::query()
                ->abilityBlocked()
                ->forImpersonator($impersonator)
                ->where('created_at', '>=', $since)
                ->count();

            // If the count of blocked abilities exceeds the limit, add a flag and increase the risk score
            if ($blockedCount > $blockedLimit) {
                $flags[] = 'too_many_blocked_abilities';
                $score += 25;
            }
        }

        // If no risk flags were detected, return without further action
        if ($flags === []) {
            return;
        }

        // Determine the risk score threshold from the configuration, defaulting to 1 if not specified
        $threshold = (int) config('masquerade.risk.score_threshold', 1);
        if ($score < $threshold) {
            return;
        }

        // Prepare metadata for the risk detection event, including the trigger, flags, and score
        $metadata = [
            'trigger' => $trigger,
            'flags' => $flags,
            'score' => $score,
        ];

        // Record the risk detection in the masquerade log with the relevant details, including the
        // impersonator, target, guard, reason, metadata, UUID, category, risk score, and risk flags
        $this->record(
            action: MasqueradeAction::RiskDetected,
            impersonator: $impersonator,
            target: $target,
            guard: $this->guard() ?? $this->resolveGuard(null),
            reason: $this->reason(),
            metadata: $metadata,
            startedAt: $this->startedAt(),
            endedAt: null,
            uuid: $uuid,
            category: $this->category(),
            riskScore: $score,
            riskFlags: $flags,
            extensionCount: $this->extensionCount(),
        );

        // Dispatch the MasqueradeRiskDetected event with the relevant information, including the risk
        // score, flags, impersonator, target, UUID, trigger, and metadata
        $this->events->dispatch(new MasqueradeRiskDetected(
            score: $score,
            flags: $flags,
            impersonator: $impersonator,
            target: $target,
            uuid: $uuid,
            trigger: $trigger,
            metadata: $metadata,
        ));
    }

    /**
     * Record a masquerade action in the log.
     *
     * @param  Masquerade
     * @param  array<string, mixed>  $metadata
     * @param  array<int, string>  $riskFlags
     */
    private function record(
        MasqueradeAction $action,
        ?Authenticatable $impersonator,
        ?Authenticatable $target,
        string $guard,
        ?string $reason,
        array $metadata,
        ?CarbonImmutable $startedAt,
        ?CarbonImmutable $endedAt,
        string $uuid,
        ?string $category = null,
        ?string $ability = null,
        ?string $endedReason = null,
        int $riskScore = 0,
        array $riskFlags = [],
        int $extensionCount = 0,
    ): void {
        // If logging is disabled in the configuration, return without recording
        if (! (bool) config('masquerade.logging.enabled', true)) {
            return;
        }

        // Get the model class for masquerade logs from the configuration, defaulting to MasqueradeLog if not specified or invalid
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Validate that the model class is a string and exists, returning without recording if not
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $modelClass::query()->create([
            'masquerade_uuid' => $uuid,
            'action' => $action->value,
            'guard' => $guard,
            'category' => $category,
            'ability' => $ability,
            'ended_reason' => $endedReason,
            'extension_count' => $extensionCount,
            'risk_score' => $riskScore,
            'risk_flags' => $riskFlags === [] ? null : $riskFlags,
            'impersonator_type' => $this->morphTypeFor($impersonator),
            'impersonator_id' => $impersonator?->getAuthIdentifier(),
            'target_type' => $this->morphTypeFor($target),
            'target_id' => $target?->getAuthIdentifier(),
            'reason' => $reason,
            'ip_address' => (bool) config('masquerade.logging.store_ip_address', true) ? $this->request->ip() : null,
            'user_agent' => (bool) config('masquerade.logging.store_user_agent', true) ? $this->request->userAgent() : null,
            'metadata' => $metadata,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);
    }
}
