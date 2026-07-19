<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

final class MasqueradeSecurityTest extends TestCase
{
    public function test_reason_can_be_required_before_starting(): void
    {
        config()->set('masquerade.security.require_reason', true);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        $this->expectException(CannotMasqueradeException::class);

        Masquerade::start($target);
    }

    public function test_same_user_masquerade_is_blocked_by_default_and_logged_as_denied(): void
    {
        $admin = $this->createUser(['is_admin' => true]);

        Auth::login($admin);

        try {
            Masquerade::start($admin, reason: 'Testing');
        } catch (CannotMasqueradeException) {
            // Expected.
        }

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
        config()->set('masquerade.logging.log_denied_attempts', false);

        $admin = $this->createUser(['is_admin' => true]);

        Auth::login($admin);

        try {
            Masquerade::start($admin);
        } catch (CannotMasqueradeException) {
            // Expected.
        }

        $this->assertSame(0, MasqueradeLog::query()->denied()->count());
    }

    public function test_model_permission_checks_can_be_disabled(): void
    {
        config()->set('masquerade.permissions.use_model_methods', false);

        $user = $this->createUser();
        $target = $this->createUser();

        Auth::login($user);

        Masquerade::start($target);

        $this->assertTrue(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($target));
    }
}
