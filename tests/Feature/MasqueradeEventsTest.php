<?php

declare(strict_types=1);

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

final class MasqueradeEventsTest extends TestCase
{
    public function test_start_and_stop_events_are_dispatched(): void
    {
        Event::fake([
            MasqueradeStarted::class,
            MasqueradeEnded::class,
        ]);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target, reason: 'Support', metadata: ['ticket' => 'SUP-101']);
        $uuid = Masquerade::uuid();
        Masquerade::stop();

        Event::assertDispatched(MasqueradeStarted::class, function (MasqueradeStarted $event) use ($admin, $target): bool {
            return $event->impersonator->getAuthIdentifier() === $admin->id
                && $event->target->getAuthIdentifier() === $target->id
                && $event->reason === 'Support'
                && $event->metadata['ticket'] === 'SUP-101';
        });

        Event::assertDispatched(MasqueradeEnded::class, function (MasqueradeEnded $event) use ($uuid): bool {
            return $event->uuid === $uuid;
        });
    }

    public function test_denied_event_is_dispatched(): void
    {
        Event::fake([MasqueradeDenied::class]);

        $user = $this->createUser();
        $target = $this->createUser();

        Auth::login($user);

        try {
            Masquerade::start($target, reason: 'Support');
        } catch (CannotMasqueradeException) {
            // Expected.
        }

        Event::assertDispatched(MasqueradeDenied::class, function (MasqueradeDenied $event) use ($user, $target): bool {
            return $event->impersonator->getAuthIdentifier() === $user->id
                && $event->target->getAuthIdentifier() === $target->id
                && $event->reason === 'Support'
                && is_string($event->uuid);
        });
    }

    public function test_extended_event_is_dispatched(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));
        Event::fake([MasqueradeExtended::class]);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target);
        Masquerade::extend(10, 'Need more time');

        Event::assertDispatched(MasqueradeExtended::class, function (MasqueradeExtended $event): bool {
            return $event->previousExpiresAt->toIso8601String() === '2026-01-01T13:00:00+00:00'
                && $event->expiresAt->toIso8601String() === '2026-01-01T13:10:00+00:00'
                && $event->reason === 'Need more time';
        });
    }

    public function test_expired_event_is_dispatched_when_duration_middleware_stops_session(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));
        Event::fake([MasqueradeExpired::class]);

        config()->set('masquerade.duration.minutes', 1);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);

        Masquerade::start($target);
        $uuid = Masquerade::uuid();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:02:00'));

        $this->assertTrue(Masquerade::stopIfExpired());

        Event::assertDispatched(MasqueradeExpired::class, function (MasqueradeExpired $event) use ($uuid): bool {
            return $event->uuid === $uuid;
        });
    }
}
