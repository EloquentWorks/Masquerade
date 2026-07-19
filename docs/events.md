# 📡 Events

Masquerade dispatches events during the impersonation lifecycle.

## 📡 Events

```php
EloquentWorks\Masquerade\Events\MasqueradeStarted::class;
EloquentWorks\Masquerade\Events\MasqueradeEnded::class;
EloquentWorks\Masquerade\Events\MasqueradeDenied::class;
EloquentWorks\Masquerade\Events\MasqueradeExpired::class;
```

## 🎧 Listen for Started Sessions

```php
use EloquentWorks\Masquerade\Events\MasqueradeStarted;
use Illuminate\Support\Facades\Event;

Event::listen(MasqueradeStarted::class, function (MasqueradeStarted $event): void {
    logger()->info('Masquerade started.', [
        'uuid' => $event->uuid,
        'guard' => $event->guard,
    ]);
});
```

## 💡 Use Cases

Events are useful for:

- Extra audit logging
- Notifications to security channels
- Support dashboards
- Metrics and analytics
- Alerting when privileged accounts are targeted

## 🛡️ Keep Listeners Safe

Avoid throwing unhandled exceptions from listeners. A listener failure should not leave the impersonation flow in an inconsistent state.
