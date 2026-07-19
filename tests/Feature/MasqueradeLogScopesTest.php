<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

final class MasqueradeLogScopesTest extends TestCase
{
    public function test_log_scopes_filter_by_action_uuid_impersonator_and_target(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();
        $other = $this->createUser(['is_admin' => true]);

        Auth::login($admin);

        Masquerade::start($target);
        $uuid = Masquerade::uuid();
        Masquerade::extend(5);
        Masquerade::stop();

        Auth::login($other);
        Masquerade::start($admin);
        Masquerade::stop();

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
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        $log = MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000000',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'started_at' => now()->subMinutes(5),
            'ended_at' => now(),
        ]);

        $this->assertTrue($log->isAction(MasqueradeAction::Ended));
        $this->assertSame(300, $log->durationInSeconds());
    }
}
