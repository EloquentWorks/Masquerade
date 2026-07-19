# 🎭 Masquerading

Masquerading means a trusted user temporarily signs in as another user while the original account is remembered in the session.

## Start a Session

```php
use EloquentWorks\Masquerade\Facades\Masquerade;

Masquerade::start(
    target: $user,
    reason: 'Troubleshooting support ticket #1042',
    metadata: [
        'ticket_id' => 1042,
        'source' => 'support-panel',
    ],
);
```

## Provide an Explicit Impersonator

Normally the current authenticated user is the impersonator. You can pass one explicitly:

```php
Masquerade::start(
    target: $customer,
    impersonator: $admin,
    guard: 'web',
    reason: 'QA verification',
);
```

## Check the Current Session

```php
Masquerade::isMasquerading();
Masquerade::uuid();
Masquerade::startedAt();
Masquerade::expiresAt();
Masquerade::hasExpired();
```

## Read the Users

```php
$impersonator = Masquerade::impersonator();
$target = Masquerade::target();
```

## Read UI-Safe Context

```php
$context = Masquerade::context();

$context['active'];
$context['uuid'];
$context['guard'];
$context['impersonator'];
$context['target'];
$context['started_at'];
$context['expires_at'];
```

## Stop a Session

```php
Masquerade::stop();
```

Masquerade logs the original user back in, records an audit row, clears the session state, and regenerates the session ID when configured.

## Stop Only if Expired

```php
if (Masquerade::stopIfExpired()) {
    // The current session expired and was stopped.
}
```

## Helper Function

The global helper returns the same manager:

```php
masquerade()->isMasquerading();
masquerade()->stop();
```
