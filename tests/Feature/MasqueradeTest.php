<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test cases for the Masquerade functionality.
 */
final class MasqueradeTest extends TestCase
{
    public function test_admin_can_start_and_stop_masquerading(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start masquerading as the target user with a reason
        Masquerade::start($target, reason: 'Support');

        // Assert that masquerading is active
        $this->assertTrue(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($target));
        $impersonator = Masquerade::impersonator();
        $resolvedTarget = Masquerade::target();
        $this->assertInstanceOf(User::class, $impersonator);
        $this->assertInstanceOf(User::class, $resolvedTarget);
        $this->assertTrue($impersonator->is($admin));
        $this->assertTrue($resolvedTarget->is($target));

        // Stop masquerading
        Masquerade::stop();

        // Assert that masquerading has stopped and the current user is the admin again
        $this->assertFalse(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($admin));
    }

    public function test_non_admin_cannot_masquerade(): void
    {
        // Create a non-admin user and a target user
        $user = $this->createUser();
        $target = $this->createUser();

        // Log in as the non-admin user
        Auth::login($user);

        // Expect an exception when attempting to start masquerading
        $this->expectException(CannotMasqueradeException::class);

        // Attempt to start masquerading as the target user
        Masquerade::start($target);
    }

    public function test_owner_cannot_be_masqueraded(): void
    {
        // Create an admin user and an owner user
        $admin = $this->createUser(['is_admin' => true]);
        $owner = $this->createUser(['is_owner' => true]);

        // Log in as the admin user
        Auth::login($admin);

        // Expect an exception when attempting to start masquerading as the owner
        $this->expectException(CannotMasqueradeException::class);

        // Attempt to start masquerading as the owner user
        Masquerade::start($owner);
    }

    public function test_it_writes_audit_logs(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start and stop masquerading as the target user with a reason
        Masquerade::start($target, reason: 'Support ticket');
        Masquerade::stop();

        // Assert that two masquerade logs were created and verify their contents
        $this->assertSame(2, MasqueradeLog::query()->count());
        $this->assertDatabaseHas('masquerade_logs', [
            'action' => 'started',
            'impersonator_id' => $admin->id,
            'target_id' => $target->id,
            'reason' => 'Support ticket',
        ]);
        $this->assertDatabaseHas('masquerade_logs', [
            'action' => 'ended',
            'impersonator_id' => $admin->id,
            'target_id' => $target->id,
        ]);
    }
}
