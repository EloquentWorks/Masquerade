<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Data\MasqueradeSession;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

final class MasqueradeSessionTest extends TestCase
{
    public function test_it_exposes_session_context_reason_metadata_and_identity_helpers(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target, reason: 'Support ticket', metadata: ['ticket' => 'SUP-100']);

        $session = Masquerade::session();

        $this->assertInstanceOf(MasqueradeSession::class, $session);
        $this->assertTrue(Masquerade::isMasqueradingAs($target));
        $this->assertTrue(Masquerade::isMasqueradedBy($admin));
        $this->assertSame('Support ticket', Masquerade::reason());
        $this->assertSame(['ticket' => 'SUP-100'], Masquerade::metadata());
        $this->assertSame(0, Masquerade::elapsedSeconds());
        $this->assertSame(3600, Masquerade::remainingSeconds());
        $this->assertSame('SUP-100', Masquerade::context()['metadata']['ticket']);
    }

    public function test_metadata_can_be_merged_or_replaced(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target, metadata: ['ticket' => 'SUP-100']);
        Masquerade::updateMetadata(['priority' => 'high']);

        $this->assertSame([
            'ticket' => 'SUP-100',
            'priority' => 'high',
        ], Masquerade::metadata());

        Masquerade::updateMetadata(['replaced' => true], merge: false);

        $this->assertSame(['replaced' => true], Masquerade::metadata());
    }

    public function test_session_can_be_extended_and_logs_the_extension(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target);

        $originalExpiresAt = Masquerade::expiresAt();
        $newExpiresAt = Masquerade::extend(15, reason: 'Still troubleshooting');

        $this->assertNotNull($originalExpiresAt);
        $this->assertSame($originalExpiresAt->addMinutes(15)->toIso8601String(), $newExpiresAt->toIso8601String());
        $this->assertSame(1, MasqueradeLog::query()->extended()->count());
        $this->assertDatabaseHas('masquerade_logs', [
            'action' => 'extended',
            'reason' => 'Still troubleshooting',
            'impersonator_id' => $admin->id,
            'target_id' => $target->id,
        ]);
    }

    public function test_session_extension_respects_maximum_duration_cap(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        config()->set('masquerade.duration.minutes', 30);
        config()->set('masquerade.duration.max_minutes', 45);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target);

        $expiresAt = Masquerade::extend(60);

        $this->assertSame('2026-01-01T12:45:00+00:00', $expiresAt->toIso8601String());
    }
}
