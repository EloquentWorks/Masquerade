<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

final class MasqueradeTest extends TestCase
{
    public function test_admin_can_start_and_stop_masquerading(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target, reason: 'Support');

        $this->assertTrue(Masquerade::isMasquerading());
        $this->assertTrue(Auth::user()->is($target));
        $this->assertTrue(Masquerade::impersonator()?->is($admin));
        $this->assertTrue(Masquerade::target()?->is($target));

        Masquerade::stop();

        $this->assertFalse(Masquerade::isMasquerading());
        $this->assertTrue(Auth::user()->is($admin));
    }

    public function test_non_admin_cannot_masquerade(): void
    {
        $user = $this->createUser();
        $target = $this->createUser();

        Auth::login($user);

        $this->expectException(CannotMasqueradeException::class);

        Masquerade::start($target);
    }

    public function test_owner_cannot_be_masqueraded(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $owner = $this->createUser(['is_owner' => true]);

        Auth::login($admin);

        $this->expectException(CannotMasqueradeException::class);

        Masquerade::start($owner);
    }

    public function test_it_writes_audit_logs(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target, reason: 'Support ticket');
        Masquerade::stop();

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
