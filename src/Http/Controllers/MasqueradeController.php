<?php

namespace EloquentWorks\Masquerade\Http\Controllers;

use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\Feature\Http\Controllers\MasqueradeControllerTest;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * @see MasqueradeControllerTest
 */
final class MasqueradeController extends Controller
{
    /**
     * Start masquerading as another user.
     */
    public function start(Request $request, string|int $user): RedirectResponse
    {
        // Validate the user model configuration
        $modelClass = config('masquerade.user_model', 'App\\Models\\User');

        // Validate that the model class is a string and exists
        abort_unless(is_string($modelClass) && class_exists($modelClass), 500, 'Masquerade user model is not configured correctly.');
        abort_unless(is_subclass_of($modelClass, Model::class), 500, 'Masquerade user model must be an Eloquent model.');

        /** @var class-string<Model> $modelClass */
        $target = $modelClass::query()->findOrFail($user);

        // Validate that the target model implements Authenticatable
        abort_unless($target instanceof Authenticatable, 500, 'Masquerade user model must implement Authenticatable.');

        // Validate the request data
        $data = $request->validate([
            'reason' => [(bool) config('masquerade.security.require_reason', false) ? 'required' : 'nullable', 'string', 'max:1000'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        // Start masquerading
        Masquerade::start(
            target: $target,
            reason: $data['reason'] ?? null,
            metadata: [
                'started_from_route' => true,
                'redirect_to' => $data['redirect_to'] ?? null,
            ],
        );

        // Redirect to the specified URL or the default route after starting masquerade
        return redirect($this->safeRedirectTo($request, $data['redirect_to'] ?? null, 'redirect_after_start'))
            ->with('status', config('masquerade.messages.started'));
    }

    /**
     * Stop masquerading and return to the original user.
     */
    public function stop(Request $request): RedirectResponse
    {
        // Validate the request data
        $data = $request->validate([
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        // Stop masquerading
        Masquerade::stop();

        // Redirect to the specified URL or the default route after stopping masquerade
        return redirect($this->safeRedirectTo($request, $data['redirect_to'] ?? null, 'redirect_after_stop'))
            ->with('status', config('masquerade.messages.stopped'));
    }

    /**
     * Safely determine the redirect URL based on the provided input and configuration.
     */
    private function safeRedirectTo(Request $request, mixed $redirectTo, string $defaultConfigKey): string
    {
        // Get the default redirect URL from configuration
        $default = (string) config("masquerade.routes.{$defaultConfigKey}", '/');

        // Validate the provided redirect URL
        if (! is_string($redirectTo) || trim($redirectTo) === '') {
            return $default;
        }

        // Check if the redirect URL is a relative path (starts with a single slash)
        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return $redirectTo;
        }

        // Check if the redirect URL is an absolute URL and validate its host
        $host = parse_url($redirectTo, PHP_URL_HOST);

        // If the host is not a valid string or is empty, return the default redirect URL
        if (! is_string($host) || $host === '') {
            return $default;
        }

        // Check if the host is allowed based on the current request host and the allowed redirect hosts from configuration
        $allowedHosts = config('masquerade.routes.allowed_redirect_hosts', []);
        $allowedHosts = is_array($allowedHosts) ? $allowedHosts : [];

        // If the host is the same as the current request host or is in the allowed hosts list, return the provided redirect URL
        if ($host === $request->getHost() || in_array($host, $allowedHosts, true)) {
            return $redirectTo;
        }

        // If none of the conditions are met, return the default redirect URL
        return $default;
    }
}
