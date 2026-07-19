<?php

namespace EloquentWorks\Masquerade\Http\Controllers;

use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Class MasqueradeController
 *
 * This controller handles the starting and stopping of masquerade sessions for users.
 */
final class MasqueradeController extends Controller
{
    /**
     * Start a masquerade session for the specified user.
     *
     * @param Request $request The incoming HTTP request.
     * @param string|int $user The ID of the user to impersonate.
     * @return RedirectResponse A redirect response to the specified URL or default route.
     */
    public function start(Request $request, string|int $user): RedirectResponse
    {
        // Validate the user model configuration
        $modelClass = config('masquerade.user_model', 'App\\Models\\User');

        // Ensure the configured model class is a valid string and exists
        abort_unless(is_string($modelClass) && class_exists($modelClass), 500, 'Masquerade user model is not configured correctly.');
        abort_unless(is_subclass_of($modelClass, Model::class), 500, 'Masquerade user model must be an Eloquent model.');

        /** @var class-string<Model> $modelClass */
        $target = $modelClass::query()->findOrFail($user);

        // Ensure the target user model implements the Authenticatable interface
        abort_unless($target instanceof Authenticatable, 500, 'Masquerade user model must implement Authenticatable.');

        // Validate the request data
        $data = $request->validate([
            'reason' => [(bool) config('masquerade.security.require_reason', false) ? 'required' : 'nullable', 'string', 'max:1000'],
            'redirect_to' => ['nullable', 'string', 'max:2048'],
        ]);

        // Start the masquerade session using the Masquerade facade
        Masquerade::start(
            target: $target,
            reason: $data['reason'] ?? null,
            metadata: [
                'started_from_route' => true,
                'redirect_to' => $data['redirect_to'] ?? null,
            ],
        );

        // Redirect the user to the specified URL or default route after starting the masquerade session
        return redirect($data['redirect_to'] ?? config('masquerade.routes.redirect_after_start', '/'))
            ->with('status', config('masquerade.messages.started'));
    }

    /**
     * Stop the current masquerade session.
     *
     * @return RedirectResponse A redirect response to the specified URL or default route.
     */
    public function stop(): RedirectResponse
    {
        // Stop the masquerade session using the Masquerade facade
        Masquerade::stop();

        // Redirect the user to the specified URL or default route after stopping the masquerade session
        return redirect(config('masquerade.routes.redirect_after_stop', '/'))
            ->with('status', config('masquerade.messages.stopped'));
    }
}
