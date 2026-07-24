<?php

namespace EloquentWorks\Masquerade\Http\Controllers;

use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Controller for handling masquerade actions.
 */
final class MasqueradeController extends Controller
{
    /**
     * Start masquerading as a specified user.
     *
     * @param  Request  $request
     * @param  string|int  $user
     * @return RedirectResponse
     */
    public function start(Request $request, string|int $user): RedirectResponse
    {
        $modelClass = config('masquerade.user_model', 'App\\Models\\User');

        // Validate that the configured model class is a valid string and exists
        abort_unless(is_string($modelClass) && class_exists($modelClass), 500, 'Masquerade user model is not configured correctly.');
        abort_unless(is_subclass_of($modelClass, Model::class), 500, 'Masquerade user model must be an Eloquent model.');

        /** @var class-string<Model> $modelClass */
        $target = $modelClass::query()->findOrFail($user);

        // Validate that the target user model implements the Authenticatable interface
        abort_unless($target instanceof Authenticatable, 500, 'Masquerade user model must implement Authenticatable.');

        // Check if the current user can masquerade as the target user
        $data = $request->validate([
            'reason' => [(bool) config('masquerade.security.require_reason', false) ? 'required' : 'nullable', 'string', 'max:1000'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        // Check if the current user can masquerade as the target user
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
     *
     * @param  Request  $request
     * @return RedirectResponse
     */
    public function stop(Request $request): RedirectResponse
    {
        // Validate the redirect_to parameter
        $data = $request->validate([
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        // Stop masquerading and return to the original user
        Masquerade::stop();

        // Redirect to the specified URL or the default route after stopping masquerade
        return redirect($this->safeRedirectTo($request, $data['redirect_to'] ?? null, 'redirect_after_stop'))
            ->with('status', config('masquerade.messages.stopped'));
    }

    /**
     * Determine a safe URL to redirect to after a masquerade action.
     *
     * @param  Request  $request
     * @param  mixed  $redirectTo
     * @param  string  $defaultConfigKey
     * @return string
     */
    private function safeRedirectTo(Request $request, mixed $redirectTo, string $defaultConfigKey): string
    {
        $default = (string) config("masquerade.routes.{$defaultConfigKey}", '/');

        // If the redirectTo parameter is not a valid string or is empty, return the default URL
        if (! is_string($redirectTo) || trim($redirectTo) === '') {
            return $default;
        }

        // If the redirectTo parameter is a relative path (starts with a single slash), return it as is
        if (str_starts_with($redirectTo, '/') && ! str_starts_with($redirectTo, '//')) {
            return $redirectTo;
        }

        // Parse the host from the redirectTo URL
        $host = parse_url($redirectTo, PHP_URL_HOST);

        // If the host is not a valid string or is empty, return the default URL
        if (! is_string($host) || $host === '') {
            return $default;
        }

        // Check if the host is allowed for redirection based on the configuration
        $allowedHosts = config('masquerade.routes.allowed_redirect_hosts', []);
        $allowedHosts = is_array($allowedHosts) ? $allowedHosts : [];

        // If the host matches the current request's host or is in the list of allowed hosts, return the redirectTo URL; otherwise, return the default URL
        if ($host === $request->getHost() || in_array($host, $allowedHosts, true)) {
            return $redirectTo;
        }

        return $default;
    }
}
