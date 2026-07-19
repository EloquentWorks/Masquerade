<?php

namespace EloquentWorks\Masquerade;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Data\MasqueradeSession;
use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Events\MasqueradeDenied;
use EloquentWorks\Masquerade\Events\MasqueradeEnded;
use EloquentWorks\Masquerade\Events\MasqueradeExpired;
use EloquentWorks\Masquerade\Events\MasqueradeExtended;
use EloquentWorks\Masquerade\Events\MasqueradeStarted;
use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

/**
 * Manages masquerade sessions for impersonating users.
 */
final class MasqueradeManager
{
    /**
     * Create a new MasqueradeManager instance.
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
    ): void {
        // Resolve the guard to use for authentication
        $guard = $this->resolveGuard($guard);
        $impersonator ??= $this->auth->guard($guard)->user();

        // Check if the impersonator is authenticated
        if (! $impersonator instanceof Authenticatable) {
            throw CannotMasqueradeException::because('No authenticated impersonator was found.');
        }

        // Check if nested masquerade sessions are allowed
        if ($this->isMasquerading() && ! (bool) config('masquerade.security.allow_nested', false)) {
            throw CannotMasqueradeException::because('Nested masquerade sessions are disabled.');
        }

        // Check if a reason is required and if it is provided
        if ((bool) config('masquerade.security.require_reason', false) && blank($reason)) {
            throw CannotMasqueradeException::because('A reason is required before starting a masquerade session.');
        }

        // Check if the impersonator is allowed to masquerade as the target user
        if (! $this->canMasquerade($impersonator, $target)) {
            $uuid = (string) Str::uuid();

            // Record the denied masquerade attempt and dispatch the MasqueradeDenied event
            $this->recordDeniedAttempt($impersonator, $target, $guard, $reason, $metadata, $uuid);
            $this->events->dispatch(new MasqueradeDenied($impersonator, $target, $guard, $reason, $metadata, $uuid));

            // Throw an exception with a custom message if configured, otherwise use a default message
            throw CannotMasqueradeException::because((string) config('masquerade.messages.denied', 'You are not allowed to masquerade as this user.'));
        }

        // Determine the current time and the masquerade session duration
        $now = CarbonImmutable::now();
        $uuid = (string) Str::uuid();
        $minutes = max(1, (int) config('masquerade.duration.minutes', 60));

        // Store masquerade session data in the session
        $this->session->put($this->key('active'), true);
        $this->session->put($this->key('uuid'), $uuid);
        $this->session->put($this->key('guard'), $guard);
        $this->session->put($this->key('impersonator_id'), $impersonator->getAuthIdentifier());
        $this->session->put($this->key('impersonator_type'), $impersonator::class);
        $this->session->put($this->key('target_id'), $target->getAuthIdentifier());
        $this->session->put($this->key('target_type'), $target::class);
        $this->session->put($this->key('reason'), $reason);
        $this->session->put($this->key('metadata'), $metadata);
        $this->session->put($this->key('started_at'), $now->toIso8601String());
        $this->session->put($this->key('expires_at'), $now->addMinutes($minutes)->toIso8601String());

        // Record the masquerade start action in the logs
        $this->record(MasqueradeAction::Started, $impersonator, $target, $guard, $reason, $metadata, $now, null, $uuid);

        // Log in as the target user without "remember me"
        $this->auth->guard($guard)->login($target, false);

        // Regenerate the session ID for security purposes if configured to do so
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch the MasqueradeStarted event
        $this->events->dispatch(new MasqueradeStarted($impersonator, $target, $guard, $reason, $metadata, $uuid));
    }

    /**
     * Stop masquerading and return to the original user.
     *
     * @throws CannotMasqueradeException
     */
    public function stop(?string $guard = null, bool $expired = false): void
    {
        // If there is no active masquerade session, simply return without doing anything
        if (! $this->isMasquerading()) {
            return;
        }

        // Resolve the guard to use for authentication, defaulting to the one stored in the session
        $guard = $this->resolveGuard($guard ?? $this->session->get($this->key('guard')));
        $uuid = $this->uuid() ?? (string) Str::uuid();
        $reason = $this->reason();
        $metadata = $this->metadata();
        $startedAt = $this->startedAt();

        // If the impersonator is available, log them back in; otherwise, log out the current user if configured to do so
        $impersonator = $this->impersonator();
        $target = $this->target() ?? $this->auth->guard($guard)->user();

        // If the impersonator is available, log them back in; otherwise, log out the current user if configured to do so
        if ($impersonator instanceof Authenticatable) {
            $this->auth->guard($guard)->login($impersonator, false);
        } elseif ((bool) config('masquerade.security.logout_on_missing_original_user', true)) {
            $this->auth->guard($guard)->logout();
        }

        // Record the masquerade end or expired action in the logs
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
        );

        // Dispatch the appropriate event based on whether the session expired or ended normally
        $this->clear();

        // Regenerate the session ID for security purposes if configured to do so
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch the appropriate event based on whether the session expired or ended normally
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
        // Ensure that there is an active masquerade session before attempting to extend it
        if (! $this->isMasquerading()) {
            throw CannotMasqueradeException::because('No active masquerade session was found.');
        }

        // Ensure that masquerade session extension is allowed in the configuration
        if (! (bool) config('masquerade.duration.allow_extension', true)) {
            throw CannotMasqueradeException::because('Masquerade session extension is disabled.');
        }

        // Ensure that the number of minutes to extend is at least 1
        $minutes = max(1, $minutes);
        $previousExpiresAt = $this->expiresAt() ?? CarbonImmutable::now();
        $expiresAt = $previousExpiresAt->addMinutes($minutes);
        $startedAt = $this->startedAt();
        $maxMinutes = (int) config('masquerade.duration.max_minutes', 0);

        // If a maximum duration is set, ensure that the new expiration time does not exceed it
        if ($startedAt instanceof CarbonImmutable && $maxMinutes > 0) {
            $maximumExpiresAt = $startedAt->addMinutes($maxMinutes);

            // If the new expiration time exceeds the maximum allowed, set it to the maximum
            if ($expiresAt->greaterThan($maximumExpiresAt)) {
                $expiresAt = $maximumExpiresAt;
            }
        }

        // Update the expiration time in the session
        $this->session->put($this->key('expires_at'), $expiresAt->toIso8601String());

        // Update the metadata with the extension details
        $metadata = array_merge($this->metadata(), [
            'extended_by_minutes' => $minutes,
            'previous_expires_at' => $previousExpiresAt->toIso8601String(),
            'new_expires_at' => $expiresAt->toIso8601String(),
        ]);

        // Record the masquerade extension action in the logs
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
        );

        // Dispatch the MasqueradeExtended event with the relevant details
        $this->events->dispatch(new MasqueradeExtended(
            impersonator: $this->impersonator(),
            target: $this->target(),
            guard: $this->guard() ?? $this->resolveGuard(null),
            uuid: $this->uuid() ?? '',
            previousExpiresAt: $previousExpiresAt,
            expiresAt: $expiresAt,
            reason: $reason,
        ));

        // Return the new expiration time for the masquerade session
        return $expiresAt;
    }

    /**
     * Merge or replace metadata on the active masquerade session.
     *
     * @param  array<string, mixed>  $metadata
     *
     * @throws CannotMasqueradeException
     */
    public function updateMetadata(array $metadata, bool $merge = true): void
    {
        // Ensure that there is an active masquerade session before attempting to update metadata
        if (! $this->isMasquerading()) {
            throw CannotMasqueradeException::because('No active masquerade session was found.');
        }

        // Update the metadata in the session, either merging with existing metadata or replacing it entirely
        $this->session->put(
            $this->key('metadata'),
            $merge ? array_merge($this->metadata(), $metadata) : $metadata,
        );
    }

    /**
     * Determine if the current session is masquerading.
     */
    public function isMasquerading(): bool
    {
        // Check if the 'active' key in the session indicates that masquerading is active, defaulting to false if not set
        return (bool) $this->session->get($this->key('active'), false);
    }

    /**
     * Determine if the current masquerade session has expired.
     */
    public function hasExpired(): bool
    {
        // Check if there is an active masquerade session; if not, it cannot be expired
        if (! $this->isMasquerading()) {
            return false;
        }

        // Check if masquerade session expiration is enabled in the configuration; if not, it cannot be expired
        if (! (bool) config('masquerade.duration.enabled', true)) {
            return false;
        }

        // Check if the current time is past the expiration time of the masquerade session
        $expiresAt = $this->expiresAt();

        // If the expiration time is not set or is not a valid CarbonImmutable instance, consider the session as not expired
        return $expiresAt instanceof CarbonImmutable && $expiresAt->isPast();
    }

    /**
     * Stop the session if it expired.
     *
     * @return bool True if the session was stopped due to expiration, false otherwise.
     */
    public function stopIfExpired(): bool
    {
        // If the masquerade session has not expired, return false without stopping it
        if (! $this->hasExpired()) {
            return false;
        }

        // If the masquerade session has expired, stop it and return true
        $this->stop(expired: true);

        // Return true to indicate that the masquerade session was stopped due to expiration
        return true;
    }

    /**
     * Determine if an impersonator may masquerade as a target.
     */
    public function canMasquerade(Authenticatable $impersonator, Authenticatable $target): bool
    {
        // Check if masquerading as the same user is allowed in the configuration; if not, deny it
        if (! (bool) config('masquerade.security.allow_same_user', false)) {
            if ($impersonator::class === $target::class && $impersonator->getAuthIdentifier() === $target->getAuthIdentifier()) {
                return false;
            }
        }

        // Check if model methods for permission checks are enabled in the configuration; if not, allow masquerading
        if (! (bool) config('masquerade.permissions.use_model_methods', true)) {
            return true;
        }

        // Check if the impersonator has a method to determine if they can masquerade as the target, and if so, call it
        $impersonatorMethod = (string) config('masquerade.permissions.impersonator_method', 'canMasquerade');
        $targetMethod = (string) config('masquerade.permissions.target_method', 'canBeMasqueradedBy');

        // Check if the target has a method to determine if they can be masqueraded by the impersonator, and if so, call it
        if (method_exists($impersonator, $impersonatorMethod) && $impersonator->{$impersonatorMethod}($target) === false) {
            return false;
        }

        // Check if the target has a method to determine if they can be masqueraded by the impersonator, and if so, call it
        if (method_exists($target, $targetMethod) && $target->{$targetMethod}($impersonator) === false) {
            return false;
        }

        // If all checks pass, allow masquerading
        return true;
    }

    /**
     * Determine if the current session is masquerading as the given user.
     */
    public function isMasqueradingAs(Authenticatable $target): bool
    {
        // Check if the current target of the masquerade session matches the given target user
        $currentTarget = $this->target();

        // Return true if the current target is an instance of Authenticatable, has the same class as the given target, and has the same authentication identifier
        return $currentTarget instanceof Authenticatable
            && $currentTarget::class === $target::class
            && $currentTarget->getAuthIdentifier() === $target->getAuthIdentifier();
    }

    /**
     * Determine if the current session was started by the given user.
     */
    public function isMasqueradedBy(Authenticatable $impersonator): bool
    {
        // Check if the current impersonator of the masquerade session matches the given impersonator user
        $currentImpersonator = $this->impersonator();

        // Return true if the current impersonator is an instance of Authenticatable, has the same class as the given impersonator, and has the same authentication identifier
        return $currentImpersonator instanceof Authenticatable
            && $currentImpersonator::class === $impersonator::class
            && $currentImpersonator->getAuthIdentifier() === $impersonator->getAuthIdentifier();
    }

    /**
     * Get the original impersonator model.
     */
    public function impersonator(): ?Authenticatable
    {
        // Resolve the impersonator model from the session data using the stored impersonator type and ID
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
        // Resolve the target model from the session data using the stored target type and ID
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
        // Get the UUID of the current masquerade session from the session data
        $uuid = $this->session->get($this->key('uuid'));

        // Return the UUID if it is a string, otherwise return null
        return is_string($uuid) ? $uuid : null;
    }

    /**
     * Get the guard name used for the current masquerade session.
     */
    public function guard(): ?string
    {
        // Get the guard name used for the current masquerade session from the session data
        $guard = $this->session->get($this->key('guard'));

        // Return the guard name if it is a string, otherwise return null
        return is_string($guard) ? $guard : null;
    }

    /**
     * Get the reason for the current masquerade session.
     */
    public function reason(): ?string
    {
        // Get the reason for the current masquerade session from the session data
        $reason = $this->session->get($this->key('reason'));

        // Return the reason if it is a string, otherwise return null
        return is_string($reason) ? $reason : null;
    }

    /**
     * Get the metadata for the current masquerade session.
     *
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        // Get the metadata for the current masquerade session from the session data
        $metadata = $this->session->get($this->key('metadata'), []);

        // Return the metadata if it is an array, otherwise return an empty array
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Get the start time of the current masquerade session.
     */
    public function startedAt(): ?CarbonImmutable
    {
        // Get the start time of the current masquerade session from the session data and parse it into a CarbonImmutable instance
        return $this->parseTime($this->session->get($this->key('started_at')));
    }

    /**
     * Get the expiration time of the current masquerade session.
     */
    public function expiresAt(): ?CarbonImmutable
    {
        return $this->parseTime($this->session->get($this->key('expires_at')));
    }

    /**
     * Get the number of seconds that have elapsed since the masquerade session started.
     */
    public function elapsedSeconds(): ?int
    {
        // Get the start time of the current masquerade session
        $startedAt = $this->startedAt();

        // If the start time is not set or is not a valid CarbonImmutable instance, return null
        if (! $startedAt instanceof CarbonImmutable) {
            return null;
        }

        // Calculate the number of seconds that have elapsed since the masquerade session started by comparing the start time with the current time
        return (int) max(0, $startedAt->diffInSeconds(CarbonImmutable::now(), false));
    }

    /**
     * Get the number of seconds remaining until the masquerade session expires.
     */
    public function remainingSeconds(): ?int
    {
        // Get the expiration time of the current masquerade session
        $expiresAt = $this->expiresAt();

        // If the expiration time is not set or is not a valid CarbonImmutable instance, return null
        if (! $expiresAt instanceof CarbonImmutable) {
            return null;
        }

        // Calculate the number of seconds remaining until the masquerade session expires by comparing the expiration time with the current time
        return (int) max(0, CarbonImmutable::now()->diffInSeconds($expiresAt, false));
    }

    /**
     * Get a typed snapshot of the current masquerade session.
     */
    public function session(): ?MasqueradeSession
    {
        // If there is no active masquerade session, return null
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
        );
    }

    /**
     * Return a small context array that is safe for UI display.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        // Return a small context array that is safe for UI display.
        $session = $this->session();

        // If there is no active masquerade session, return an array with default values indicating that masquerading is not active
        if (! $session instanceof MasqueradeSession) {
            return [
                'active' => false,
                'uuid' => null,
                'guard' => null,
                'impersonator' => null,
                'target' => null,
                'reason' => null,
                'metadata' => [],
                'started_at' => null,
                'expires_at' => null,
                'elapsed_seconds' => null,
                'remaining_seconds' => null,
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
        // Clear all masquerade session keys from the session storage.
        $this->session->forget($this->baseKey());
    }

    /**
     * Resolve the guard to use for authentication.
     */
    private function resolveGuard(?string $guard): string
    {
        // Resolve the guard to use for authentication, defaulting to the configured masquerade guard or the default auth guard if not provided
        $guard ??= config('masquerade.guard');
        $guard ??= config('auth.defaults.guard', 'web');

        // Return the resolved guard as a string
        return (string) $guard;
    }

    /**
     * Get the base session key for masquerade data.
     */
    private function baseKey(): string
    {
        // Get the base session key for masquerade data, defaulting to 'masquerade' if not configured
        return (string) config('masquerade.session_key', 'masquerade');
    }

    /**
     * Get the full session key for a specific masquerade data item.
     */
    private function key(string $name): string
    {
        // Get the full session key for a specific masquerade data item by appending the item name to the base key
        return $this->baseKey().'.'.$name;
    }

    /**
     * Parse a time value into a CarbonImmutable instance.
     */
    private function parseTime(mixed $value): ?CarbonImmutable
    {
        // Parse a time value into a CarbonImmutable instance, returning null if the value is not a valid string or is empty
        if (! is_string($value) || $value === '') {
            return null;
        }

        // Attempt to parse the time value into a CarbonImmutable instance, returning null if parsing fails
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve an Authenticatable model from its type and ID.
     */
    private function resolveAuthenticatable(mixed $type, mixed $id): ?Authenticatable
    {
        // Resolve an Authenticatable model from its type and ID, returning null if the type is not a valid string or the ID is null
        if (! is_string($type) || $type === '' || $id === null) {
            return null;
        }

        // Check if the type is a valid class that is a subclass of Illuminate\Database\Eloquent\Model; if not, return null
        if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $type */
        $model = $type::query()->find($id);

        // Return the resolved model if it is an instance of Authenticatable, otherwise return null
        return $model instanceof Authenticatable ? $model : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function recordDeniedAttempt(
        Authenticatable $impersonator,
        Authenticatable $target,
        string $guard,
        ?string $reason,
        array $metadata,
        string $uuid,
    ): void {
        // If logging of denied masquerade attempts is disabled in the configuration, return without recording the attempt
        if (! (bool) config('masquerade.logging.log_denied_attempts', true)) {
            return;
        }

        // Record the denied masquerade attempt in the logs with the provided details, including the impersonator, target, guard, reason, metadata, and UUID
        $this->record(MasqueradeAction::Denied, $impersonator, $target, $guard, $reason, $metadata, CarbonImmutable::now(), CarbonImmutable::now(), $uuid);
    }

    /**
     * Get the morph type for an Authenticatable model.
     */
    private function morphTypeFor(?Authenticatable $model): ?string
    {
        // Get the morph type for an Authenticatable model, returning null if the model is not an instance of Authenticatable or if its authentication identifier is null
        if (! $model instanceof Authenticatable || $model->getAuthIdentifier() === null) {
            return null;
        }

        // If the model is an instance of Illuminate\Database\Eloquent\Model, return its morph class; otherwise, return the class name of the model
        if ($model instanceof Model) {
            return $model->getMorphClass();
        }

        // Return the class name of the model as the morph type
        return $model::class;
    }

    /**
     * @param  array<string, mixed>  $metadata
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
    ): void {
        // If logging of masquerade actions is disabled in the configuration, return without recording the action
        if (! (bool) config('masquerade.logging.enabled', true)) {
            return;
        }

        // Get the model class to use for logging masquerade actions from the configuration, defaulting to MasqueradeLog::class if not set
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // If the model class is not a valid string or does not exist, return without recording the action
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $modelClass::query()->create([
            'masquerade_uuid' => $uuid,
            'action' => $action->value,
            'guard' => $guard,
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
