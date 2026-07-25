<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test cases for the MasqueradeLog model and its query scopes.
 */
final class MasqueradeLogScopesTest extends TestCase
{
    public function test_log_scopes_filter_by_action_uuid_impersonator_and_target(): void
    {
        // Create an admin user, a target user, and another admin user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();
        $other = $this->createUser(['is_admin' => true]);

        // Log in as the admin user and start masquerading as the target user
        Auth::login($admin);

        // Start masquerading as the target user, extend the session, and then stop masquerading
        Masquerade::start($target);
        $uuid = Masquerade::uuid();
        Masquerade::extend(5);
        Masquerade::stop();

        // Log in as the other admin user and start masquerading as the admin user, then stop masquerading
        Auth::login($other);
        Masquerade::start($admin);
        Masquerade::stop();

        // Assert that the UUID is a string and that the log counts match the expected values
        $this->assertIsString($uuid);
        $this->assertSame(2, MasqueradeLog::query()->started()->count());
        $this->assertSame(2, MasqueradeLog::query()->ended()->count());
        $this->assertSame(1, MasqueradeLog::query()->extended()->count());
        $this->assertSame(3, MasqueradeLog::query()->forImpersonator($admin)->count());
        $this->assertSame(3, MasqueradeLog::query()->forTarget($target)->count());
        $this->assertSame(3, MasqueradeLog::query()->forMasqueradeUuid($uuid)->count());
        $this->assertSame(1, MasqueradeLog::query()->forMasqueradeUuid($uuid)->forAction(MasqueradeAction::Extended)->count());
    }

    public function test_log_helpers_report_action_and_duration(): void
    {
        // Set the current time to a fixed point for testing
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        // Create a masquerade log entry with a specific action and timestamps
        $log = MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000000',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);

        // Assert that the log entry correctly identifies the action and calculates the duration
        $this->assertTrue($log->isAction(MasqueradeAction::Ended));
        $this->assertSame(300, $log->durationInSeconds());
    }
}
