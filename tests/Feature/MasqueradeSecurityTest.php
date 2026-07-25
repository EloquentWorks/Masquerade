<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test cases for masquerade security features.
 */
final class MasqueradeSecurityTest extends TestCase
{
    public function test_reason_can_be_required_before_starting(): void
    {
        // Set the configuration to require a reason for masquerading.
        config()->set('masquerade.security.require_reason', true);

        // Create an admin user and a target user for masquerading.
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user.
        Auth::login($admin);

        // Expect a CannotMasqueradeException to be thrown when trying to start masquerading without a reason.
        $this->expectException(CannotMasqueradeException::class);

        // Attempt to start masquerading as the target user without providing a reason.
        Masquerade::start($target);
    }

    public function test_same_user_masquerade_is_blocked_by_default_and_logged_as_denied(): void
    {
        // Create an admin user.
        $admin = $this->createUser(['is_admin' => true]);

        // Log in as the admin user.
        Auth::login($admin);

        // Attempt to start masquerading as the admin user themselves.
        try {
            Masquerade::start($admin, reason: 'Testing');
        } catch (CannotMasqueradeException) {
            // Expected.
        }

        // Assert that masquerading is not active and that a denied attempt has been logged.
        $this->assertFalse(Masquerade::isMasquerading());
        $this->assertSame(1, MasqueradeLog::query()->denied()->count());
        $this->assertDatabaseHas('masquerade_logs', [
            'action' => 'denied',
            'impersonator_id' => $admin->id,
            'target_id' => $admin->id,
            'reason' => 'Testing',
        ]);
    }

    public function test_denied_attempt_logging_can_be_disabled(): void
    {
        // Disable logging of denied masquerade attempts in the configuration.
        config()->set('masquerade.logging.log_denied_attempts', false);

        // Create an admin user.
        $admin = $this->createUser(['is_admin' => true]);

        // Log in as the admin user.
        Auth::login($admin);

        // Attempt to start masquerading as the admin user themselves.
        try {
            Masquerade::start($admin);
        } catch (CannotMasqueradeException) {
            // Expected.
        }

        // Assert that masquerading is not active and that no denied attempts have been logged.
        $this->assertSame(0, MasqueradeLog::query()->denied()->count());
    }

    public function test_model_permission_checks_can_be_disabled(): void
    {
        // Disable model permission checks in the configuration.
        config()->set('masquerade.permissions.use_model_methods', false);

        // Create a user and a target user for masquerading.
        $user = $this->createUser();
        $target = $this->createUser();

        // Log in as the user.
        Auth::login($user);

        // Attempt to start masquerading as the target user, which would normally be denied by the model's canMasquerade method.
        Masquerade::start($target);

        // Assert that masquerading is active and that the current user is the target user.
        $this->assertTrue(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($target));
    }
}
