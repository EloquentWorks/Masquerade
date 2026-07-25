<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Events\MasqueradeDenied;
use EloquentWorks\Masquerade\Events\MasqueradeEnded;
use EloquentWorks\Masquerade\Events\MasqueradeExpired;
use EloquentWorks\Masquerade\Events\MasqueradeExtended;
use EloquentWorks\Masquerade\Events\MasqueradeStarted;
use EloquentWorks\Masquerade\Exceptions\CannotMasqueradeException;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

/**
 * Test suite for masquerade events.
 */
final class MasqueradeEventsTest extends TestCase
{
    public function test_start_and_stop_events_are_dispatched(): void
    {
        // Fake the events to prevent actual event handling during the test.
        Event::fake([
            MasqueradeStarted::class,
            MasqueradeEnded::class,
        ]);

        // Create an admin user and a target user for the masquerade.
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user to initiate the masquerade.
        Auth::login($admin);

        // Start the masquerade with a reason and metadata.
        Masquerade::start($target, reason: 'Support', metadata: ['ticket' => 'SUP-101']);
        $uuid = Masquerade::uuid();
        Masquerade::stop();

        // Assert that the MasqueradeStarted event was dispatched with the correct properties.
        Event::assertDispatched(MasqueradeStarted::class, function (MasqueradeStarted $event) use ($admin, $target): bool {
            return $event->impersonator->getAuthIdentifier() === $admin->id
                && $event->target->getAuthIdentifier() === $target->id
                && $event->reason === 'Support'
                && $event->metadata['ticket'] === 'SUP-101';
        });

        // Assert that the MasqueradeEnded event was dispatched with the correct UUID.
        Event::assertDispatched(MasqueradeEnded::class, function (MasqueradeEnded $event) use ($uuid): bool {
            return $event->uuid === $uuid;
        });
    }

    public function test_denied_event_is_dispatched(): void
    {
        // Fake the MasqueradeDenied event to prevent actual event handling during the test.
        Event::fake([MasqueradeDenied::class]);

        // Create a user and a target user for the masquerade.
        $user = $this->createUser();
        $target = $this->createUser();

        // Log in as the user to attempt the masquerade.
        Auth::login($user);

        // Attempt to start the masquerade, which is expected to throw a CannotMasqueradeException.
        try {
            Masquerade::start($target, reason: 'Support');
        } catch (CannotMasqueradeException) {
            // Expected.
        }

        // Assert that the MasqueradeDenied event was dispatched with the correct properties.
        Event::assertDispatched(MasqueradeDenied::class, function (MasqueradeDenied $event) use ($user, $target): bool {
            return $event->impersonator->getAuthIdentifier() === $user->id
                && $event->target->getAuthIdentifier() === $target->id
                && $event->reason === 'Support'
                && is_string($event->uuid);
        });
    }

    public function test_extended_event_is_dispatched(): void
    {
        // Set the current time to a fixed point for testing.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));
        Event::fake([MasqueradeExtended::class]);

        // Set the masquerade duration to 60 minutes for testing.
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user to initiate the masquerade.
        Auth::login($admin);

        // Start the masquerade and then extend it by 10 minutes with a reason.
        Masquerade::start($target);
        Masquerade::extend(10, 'Need more time');

        // Assert that the MasqueradeExtended event was dispatched with the correct previous and new expiration
        // times, as well as the reason for the extension.
        Event::assertDispatched(MasqueradeExtended::class, function (MasqueradeExtended $event): bool {
            return $event->previousExpiresAt->toIso8601String() === '2026-01-01T13:00:00+00:00'
                && $event->expiresAt->toIso8601String() === '2026-01-01T13:10:00+00:00'
                && $event->reason === 'Need more time';
        });
    }

    public function test_expired_event_is_dispatched_when_duration_middleware_stops_session(): void
    {
        // Set the current time to a fixed point for testing.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));
        Event::fake([MasqueradeExpired::class]);

        // Set the masquerade duration to 1 minute for testing.
        config()->set('masquerade.duration.minutes', 1);

        // Create an admin user and a target user for the masquerade.
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin user to initiate the masquerade.
        Auth::login($admin);

        // Start the masquerade and retrieve the UUID for later verification.
        Masquerade::start($target);
        $uuid = Masquerade::uuid();

        // Advance the time to simulate the masquerade session expiring.
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:02:00'));

        // Call the stopIfExpired method, which should trigger the MasqueradeExpired event since the session has expired.
        $this->assertTrue(Masquerade::stopIfExpired());

        // Assert that the MasqueradeExpired event was dispatched with the correct UUID.
        Event::assertDispatched(MasqueradeExpired::class, function (MasqueradeExpired $event) use ($uuid): bool {
            return $event->uuid === $uuid;
        });
    }
}
