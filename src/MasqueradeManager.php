<?php

namespace EloquentWorks\Masquerade;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Events\MasqueradeDenied;
use EloquentWorks\Masquerade\Events\MasqueradeEnded;
use EloquentWorks\Masquerade\Events\MasqueradeExpired;
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
 * Manages masquerade sessions, allowing users to impersonate other users.
 */
final class MasqueradeManager
{
    /**
     * Create a new MasqueradeManager instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @param  \Illuminate\Contracts\Events\Dispatcher  $events
     * @param  \Illuminate\Http\Request  $request
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
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $target
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator
     * @param  string|null  $guard
     * @param  string|null  $reason
     * @param  array<string, mixed>  $metadata
     *
     * @throws \EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException
     */
    public function start(
        Authenticatable $target,
        ?Authenticatable $impersonator = null,
        ?string $guard = null,
        ?string $reason = null,
        array $metadata = [],
    ): void {
        // Validate the guard and impersonator
        $guard = $this->resolveGuard($guard);
        $impersonator ??= $this->auth->guard($guard)->user();

        // Ensure the impersonator is an instance of Authenticatable
        if (! $impersonator instanceof Authenticatable) {
            throw CannotMasqueradeException::because('No authenticated impersonator was found.');
        }

        // Ensure the target is not the same as the impersonator if nested masquerading is not allowed
        if ($this->isMasquerading() && ! (bool) config('masquerade.security.allow_nested', false)) {
            throw CannotMasqueradeException::because('Nested masquerade sessions are disabled.');
        }

        // Ensure a reason is provided if required by configuration
        if ((bool) config('masquerade.security.require_reason', false) && blank($reason)) {
            throw CannotMasqueradeException::because('A reason is required before starting a masquerade session.');
        }

        // Ensure the impersonator is allowed to masquerade as the target
        if (! $this->canMasquerade($impersonator, $target)) {
            $this->events->dispatch(new MasqueradeDenied($impersonator, $target, $guard, $reason));

            // Throw an exception with a configurable message if masquerading is denied
            throw CannotMasqueradeException::because((string) config('masquerade.messages.denied', 'You are not allowed to masquerade as this user.'));
        }

        // Set up the masquerade session with relevant information
        $now = CarbonImmutable::now();
        $uuid = (string) Str::uuid();
        $minutes = (int) config('masquerade.duration.minutes', 60);

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

        // Record the start of the masquerade session in the logs
        $this->record('started', $impersonator, $target, $guard, $reason, $metadata, $now, null, $uuid);

        // Log in as the target user without "remember me" functionality
        $this->auth->guard($guard)->login($target, false);

        // Regenerate the session ID for security purposes if configured to do so
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch an event indicating that masquerading has started
        $this->events->dispatch(new MasqueradeStarted($impersonator, $target, $guard, $reason, $metadata, $uuid));
    }

    /**
     * Stop masquerading and return to the original user.
     *
     * @param  string|null  $guard
     * @param  bool  $expired
     * @return void
     */
    public function stop(?string $guard = null, bool $expired = false): void
    {
        // If the current session is not masquerading, there is nothing to stop, so we return early.
        if (! $this->isMasquerading()) {
            return;
        }

        // Resolve the guard to use for stopping the masquerade session. If no guard is provided, we retrieve it from the session.
        $guard = $this->resolveGuard($guard ?? $this->session->get($this->key('guard')));
        $uuid = (string) $this->session->get($this->key('uuid'));
        $reason = $this->session->get($this->key('reason'));
        $metadata = $this->session->get($this->key('metadata'), []);
        $startedAt = $this->startedAt();

        // Retrieve the impersonator and target users from the session. If the target user is not found, we fall back to the currently authenticated user for the specified guard.
        $impersonator = $this->impersonator();
        $target = $this->target() ?? $this->auth->guard($guard)->user();

        // If the impersonator is found, we log them back in without "remember me" functionality. If the impersonator is not found and the configuration specifies to log out on missing original user, we log out the current user.
        if ($impersonator instanceof Authenticatable) {
            $this->auth->guard($guard)->login($impersonator, false);
        } elseif ((bool) config('masquerade.security.logout_on_missing_original_user', true)) {
            $this->auth->guard($guard)->logout();
        }

        // Record the end of the masquerade session in the logs, indicating whether it ended normally or expired.
        $this->record(
            action: $expired ? 'expired' : 'ended',
            impersonator: $impersonator,
            target: $target instanceof Authenticatable ? $target : null,
            guard: $guard,
            reason: is_string($reason) ? $reason : null,
            metadata: is_array($metadata) ? $metadata : [],
            startedAt: $startedAt,
            endedAt: CarbonImmutable::now(),
            uuid: $uuid,
        );

        // Clear all masquerade-related session data to ensure that the session is no longer in a masquerading state.
        $this->clear();

        // Regenerate the session ID for security purposes if configured to do so. This helps prevent session fixation attacks by ensuring that the session ID changes after stopping masquerading.
        if ((bool) config('masquerade.security.regenerate_session_id', true)) {
            $this->session->migrate(true);
        }

        // Dispatch the appropriate event based on whether the masquerade session expired or ended normally. This allows other parts of the application to respond to the end of a masquerade session.
        if ($expired) {
            $this->events->dispatch(new MasqueradeExpired($impersonator, $target, $guard, $uuid));

            // If the masquerade session expired, we return early after dispatching the MasqueradeExpired event, as there is no need to dispatch the MasqueradeEnded event in this case.
            return;
        }

        // If the masquerade session ended normally, we dispatch the MasqueradeEnded event to notify other parts of the application that the masquerade session has concluded.
        $this->events->dispatch(new MasqueradeEnded($impersonator, $target, $guard, $uuid));
    }

    /**
     * Determine if the current session is masquerading.
     *
     * @return bool True if the current session is masquerading, false otherwise.
     */
    public function isMasquerading(): bool
    {
        // Check if the masquerade session is active by retrieving the 'active' key from the session. If the key is not set, we default to false. This method returns true if the current session is in a masquerading state, and false otherwise.
        return (bool) $this->session->get($this->key('active'), false);
    }

    /**
     * Determine if the current masquerade session has expired.
     *
     * @return bool True if the current masquerade session has expired, false otherwise.
     */
    public function hasExpired(): bool
    {
        // If the current session is not masquerading, we return false, as there is no masquerade session to check for expiration.
        if (! $this->isMasquerading()) {
            return false;
        }

        // If masquerade duration checking is disabled in the configuration, we return false, as we do not consider the session to have expired.
        if (! (bool) config('masquerade.duration.enabled', true)) {
            return false;
        }

        // Retrieve the expiration time of the masquerade session from the session data. If the expiration time is not set or is not a valid CarbonImmutable instance, we return false, as we cannot determine if the session has expired.
        $expiresAt = $this->expiresAt();

        // Return true if the expiration time is a valid CarbonImmutable instance and is in the past, indicating that the masquerade session has expired. Otherwise, return false.
        return $expiresAt instanceof CarbonImmutable && $expiresAt->isPast();
    }

    /**
     * Stop the session if it expired.
     *
     * @return bool True if the session was stopped due to expiration, false otherwise.
     */
    public function stopIfExpired(): bool
    {
        // If the current masquerade session has not expired, we return false, indicating that no action was taken to stop the session.
        if (! $this->hasExpired()) {
            return false;
        }

        // If the masquerade session has expired, we call the stop method with the 'expired' parameter set to true, indicating that the session is being stopped due to expiration. This will handle the necessary cleanup and logging for an expired session.
        $this->stop(expired: true);

        // After stopping the expired masquerade session, we return true to indicate that the session was successfully stopped due to expiration.
        return true;
    }

    /**
     * Determine if an impersonator may masquerade as a target.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $impersonator The user attempting to impersonate another user.
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $target The user being impersonated.
     * @return bool True if the impersonator may masquerade as the target, false otherwise.
     */
    public function canMasquerade(Authenticatable $impersonator, Authenticatable $target): bool
    {
        // If masquerading as the same user is not allowed, we check if the impersonator and target are the same user. If they are, we return false to indicate that masquerading is not allowed in this case.
        if (! (bool) config('masquerade.security.allow_same_user', false)) {
            if ($impersonator::class === $target::class && $impersonator->getAuthIdentifier() === $target->getAuthIdentifier()) {
                return false;
            }
        }

        // If model methods for permission checks are disabled in the configuration, we return true, allowing masquerading without further checks.
        if (! (bool) config('masquerade.permissions.use_model_methods', true)) {
            return true;
        }

        // Retrieve the method names for permission checks from the configuration. These methods are expected to be defined on the impersonator and target models.
        $impersonatorMethod = (string) config('masquerade.permissions.impersonator_method', 'canMasquerade');
        $targetMethod = (string) config('masquerade.permissions.target_method', 'canBeMasqueradedBy');

        // Check if the impersonator model has the specified method and call it with the target as an argument. If the method exists and returns false, we return false to indicate that masquerading is not allowed.
        if (method_exists($impersonator, $impersonatorMethod) && $impersonator->{$impersonatorMethod}($target) === false) {
            return false;
        }

        // Check if the target model has the specified method and call it with the impersonator as an argument. If the method exists and returns false, we return false to indicate that masquerading is not allowed.
        if (method_exists($target, $targetMethod) && $target->{$targetMethod}($impersonator) === false) {
            return false;
        }

        // If all checks pass, we return true to indicate that the impersonator may masquerade as the target.
        return true;
    }

    /**
     * Get the original impersonator model.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null The impersonator model, or null if not found.
     */
    public function impersonator(): ?Authenticatable
    {
        // Retrieve the impersonator's type and ID from the session and resolve the corresponding Authenticatable model. If the impersonator is not found or the session data is invalid, this method will return null.
        return $this->resolveAuthenticatable(
            $this->session->get($this->key('impersonator_type')),
            $this->session->get($this->key('impersonator_id')),
        );
    }

    /**
     * Get the target model being masqueraded as.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null The target model, or null if not found.
     */
    public function target(): ?Authenticatable
    {
        // Retrieve the target's type and ID from the session and resolve the corresponding Authenticatable model. If the target is not found or the session data is invalid, this method will return null.
        return $this->resolveAuthenticatable(
            $this->session->get($this->key('target_type')),
            $this->session->get($this->key('target_id')),
        );
    }

    /**
     * Get the unique identifier for the current masquerade session.
     *
     * @return string|null The UUID of the current masquerade session, or null if not found.
     */
    public function uuid(): ?string
    {
        // Retrieve the UUID of the current masquerade session from the session data. If the UUID is not found or is not a valid string, this method will return null.
        $uuid = $this->session->get($this->key('uuid'));

        // Return the UUID if it is a valid string, otherwise return null.
        return is_string($uuid) ? $uuid : null;
    }

    /**
     * Get the timestamp when the current masquerade session started.
     *
     * @return \Carbon\CarbonImmutable|null The timestamp when the masquerade session started, or null if not found.
     */
    public function startedAt(): ?CarbonImmutable
    {
        // Retrieve the start time of the current masquerade session from the session data and parse it into a CarbonImmutable instance. If the start time is not found or is not a valid string, this method will return null.
        return $this->parseTime($this->session->get($this->key('started_at')));
    }

    /**
     * Get the timestamp when the current masquerade session will expire.
     *
     * @return \Carbon\CarbonImmutable|null The timestamp when the masquerade session will expire, or null if not found.
     */
    public function expiresAt(): ?CarbonImmutable
    {
        // Retrieve the expiration time of the current masquerade session from the session data and parse it into a CarbonImmutable instance. If the expiration time is not found or is not a valid string, this method will return null.
        return $this->parseTime($this->session->get($this->key('expires_at')));
    }

    /**
     * Return a small context array that is safe for UI display.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        // Return an array containing relevant information about the current masquerade session, including whether it is active, the UUID, guard, impersonator, target, start time, and expiration time. This context can be used for UI display or debugging purposes.
        return [
            'active' => $this->isMasquerading(),
            'uuid' => $this->uuid(),
            'guard' => $this->session->get($this->key('guard')),
            'impersonator' => $this->impersonator(),
            'target' => $this->target(),
            'started_at' => $this->startedAt(),
            'expires_at' => $this->expiresAt(),
        ];
    }

    /**
     * Clear all masquerade session keys.
     *
     * This method removes all masquerade-related session data by forgetting the base key from the session. It effectively ends the masquerade session and clears any associated information.
     * @return void
     */
    public function clear(): void
    {
        // Clear all masquerade-related session data by removing the base key from the session. This effectively ends the masquerade session and removes any associated information.
        $base = $this->baseKey();

        // Remove the base key from the session, which clears all masquerade-related data.
        $this->session->forget($base);
    }

    /**
     * Resolve the guard to use for masquerading.
     *
     * @param  string|null  $guard The guard to resolve, or null to use the default guard.
     * @return string The resolved guard name.
     */
    private function resolveGuard(?string $guard): string
    {
        // If no guard is provided, we first check the configuration for a specific masquerade guard. If that is not set, we fall back to the default authentication guard defined in the application's configuration. This ensures that we always have a valid guard to use for masquerading.
        $guard ??= config('masquerade.guard');
        $guard ??= config('auth.defaults.guard', 'web');

        // Return the resolved guard name as a string. This guard will be used for authentication during the masquerade session.
        return (string) $guard;
    }

    /**
     * Get the base session key for masquerade-related data.
     *
     * @return string The base session key for masquerade-related data.
     */
    private function baseKey(): string
    {
        // Retrieve the base session key for masquerade-related data from the configuration. If no custom key is set, we default to 'masquerade'. This key is used as a prefix for all masquerade-related session data to avoid conflicts with other session data.
        return (string) config('masquerade.session_key', 'masquerade');
    }

    /**
     * Generate a full session key for a specific masquerade-related data item.
     *
     * @param  string  $name The name of the masquerade-related data item.
     * @return string The full session key for the specified data item.
     */
    private function key(string $name): string
    {
        // Generate a full session key for a specific masquerade-related data item by appending the item name to the base key.
        return $this->baseKey().'.'.$name;
    }

    /**
     * Parse a time value into a CarbonImmutable instance.
     *
     * @param  mixed  $value The value to parse, which can be a string or null.
     * @return \Carbon\CarbonImmutable|null The parsed CarbonImmutable instance, or null if parsing fails.
     */
    private function parseTime(mixed $value): ?CarbonImmutable
    {
        // If the provided value is not a string or is an empty string, we return null, indicating that there is no valid time to parse.
        if (! is_string($value) || $value === '') {
            return null;
        }

        // Attempt to parse the provided value into a CarbonImmutable instance. If parsing fails (e.g., due to an invalid date format), we catch the exception and return null to indicate that the time could not be parsed.
        try {
            return CarbonImmutable::parse($value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Resolve an Authenticatable model from its type and ID.
     *
     * @param  mixed  $type The class name of the model to resolve.
     * @param  mixed  $id The ID of the model to resolve.
     * @return \Illuminate\Contracts\Auth\Authenticatable|null The resolved Authenticatable model, or null if not found or invalid.
     */
    private function resolveAuthenticatable(mixed $type, mixed $id): ?Authenticatable
    {
        // If the provided type is not a string, is an empty string, or the ID is null, we return null, indicating that we cannot resolve a valid Authenticatable model.
        if (! is_string($type) || $type === '' || $id === null) {
            return null;
        }

        // Check if the specified type is a valid class and is a subclass of the Eloquent Model class. If not, we return null, as we cannot resolve an Authenticatable model from an invalid type.
        if (! class_exists($type) || ! is_subclass_of($type, Model::class)) {
            return null;
        }

        /** @var class-string<Model> $type */
        $model = $type::query()->find($id);

        // Return the resolved model if it is an instance of Authenticatable. If the model is not found or does not implement the Authenticatable interface, we return null.
        return $model instanceof Authenticatable ? $model : null;
    }

    /**
     * Record a masquerade action in the logs.
     *
     * @param  string  $action The action being recorded (e.g., 'started', 'ended', 'expired').
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $impersonator The user who initiated the masquerade session, or null if not available.
     * @param  \Illuminate\Contracts\Auth\Authenticatable|null  $target The user being impersonated, or null if not available.
     * @param  string  $guard The authentication guard used for the masquerade session.
     * @param  string|null  $reason The reason for starting the masquerade session, or null if not provided.
     * @param  \Carbon\CarbonImmutable|null  $startedAt The timestamp when the masquerade session started, or null if not available.
     * @param  \Carbon\CarbonImmutable|null  $endedAt The timestamp when the masquerade session ended, or null if not available.
     * @param  string  $uuid A unique identifier for the masquerade session.
     * @param  array<string, mixed>  $metadata Additional metadata associated with the masquerade session.
     * @return void
     */
    private function record(
        string $action,
        ?Authenticatable $impersonator,
        ?Authenticatable $target,
        string $guard,
        ?string $reason,
        array $metadata,
        ?CarbonImmutable $startedAt,
        ?CarbonImmutable $endedAt,
        string $uuid,
    ): void {
        // If masquerade logging is disabled in the configuration, we return early and do not record any logs.
        if (! (bool) config('masquerade.logging.enabled', true)) {
            return;
        }

        // Retrieve the model class to use for logging masquerade actions from the configuration. If no custom model class is set, we default to using the MasqueradeLog model provided by the package.
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Ensure that the specified model class is a valid string and exists. If not, we return early and do not record any logs.
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            return;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $modelClass::query()->create([
            'masquerade_uuid' => $uuid,
            'action' => $action,
            'guard' => $guard,
            'impersonator_type' => $impersonator?->getAuthIdentifier() !== null ? $impersonator::class : null,
            'impersonator_id' => $impersonator?->getAuthIdentifier(),
            'target_type' => $target?->getAuthIdentifier() !== null ? $target::class : null,
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
