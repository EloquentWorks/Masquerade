# 🎭 Laravel Masquerade v1.1.0 Features

Laravel Masquerade v1.1.0 adds safer support workflows and better audit tooling.

## 📝 Session Notes

```php
Masquerade::addNote('Checked the billing page.');

$notes = Masquerade::notes();
```

## 🎟️ Ticket Context

```php
Masquerade::start(
    target: $user,
    reason: 'Troubleshooting checkout issue.',
    metadata: [
        'ticket_id' => 'SUP-1042',
        'ticket_url' => 'https://support.example.com/tickets/SUP-1042',
    ],
    category: 'support',
);

Masquerade::ticketId();
Masquerade::ticketUrl();
Masquerade::contextValue('ticket_id');
```

## 🛡️ Ability Blocking

```php
Masquerade::assertAbilityAllowed('billing.update');
```

Middleware:

```php
Route::post('/billing', UpdateBillingController::class)
    ->middleware(['auth', 'masquerade.ability:billing.update']);
```

## 📤 Audit Exports

```bash
php artisan masquerade:export --format=csv
php artisan masquerade:export --format=json
```

## 🚨 Risk Detection

```php
'risk' => [
    'enabled' => true,
    'max_sessions_per_hour' => 10,
    'max_denied_attempts_per_hour' => 5,
    'max_blocked_abilities_per_hour' => 3,
    'score_threshold' => 1,
],
```
