<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Data\MasqueradeSession;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

/**
 * Test suite for the MasqueradeSession functionality.
 */
final class MasqueradeSessionTest extends TestCase
{
    public function test_it_exposes_session_context_reason_metadata_and_identity_helpers(): void
    {
        // Set the current time to a fixed point for testing
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        // Create an admin user and a target user for masquerading
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start a masquerade session with a reason and metadata
        Masquerade::start($target, reason: 'Support ticket', metadata: ['ticket' => 'SUP-100']);

        // Retrieve the current masquerade session
        $session = Masquerade::session();

        // Assert that the session is an instance of MasqueradeSession
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
        // Set the current time to a fixed point for testing
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start a masquerade session with initial metadata
        Masquerade::start($target, metadata: ['ticket' => 'SUP-100']);
        Masquerade::updateMetadata(['priority' => 'high']);

        // Assert that the metadata has been merged correctly
        $this->assertSame([
            'ticket' => 'SUP-100',
            'priority' => 'high',
        ], Masquerade::metadata());

        // Update the metadata with the merge option set to false, replacing the existing metadata
        Masquerade::updateMetadata(['replaced' => true], merge: false);

        // Assert that the metadata has been replaced correctly
        $this->assertSame(['replaced' => true], Masquerade::metadata());
    }

    public function test_session_can_be_extended_and_logs_the_extension(): void
    {
        // Set the current time to a fixed point for testing
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        // Create an admin user and a target user for masquerading
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start a masquerade session
        Masquerade::start($target);

        // Extend the session by 15 minutes and provide a reason for the extension
        $originalExpiresAt = Masquerade::expiresAt();
        $newExpiresAt = Masquerade::extend(15, reason: 'Still troubleshooting');

        // Assert that the new expiration time is 15 minutes later than the original expiration time
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
        // Set the current time to a fixed point for testing
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        // Set the masquerade duration configuration to test the maximum duration cap
        config()->set('masquerade.duration.minutes', 30);
        config()->set('masquerade.duration.max_minutes', 45);

        // Create an admin user and a target user for masquerading
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user
        Auth::login($admin);

        // Start a masquerade session
        Masquerade::start($target);

        // Extend the session by 60 minutes, which exceeds the maximum duration cap of 45 minutes
        $expiresAt = Masquerade::extend(60);

        // Assert that the expiration time is capped at 45 minutes from the start time
        $this->assertSame('2026-01-01T12:45:00+00:00', $expiresAt->toIso8601String());
    }
}
