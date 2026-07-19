# 🧰 Commands and Scheduling

Laravel Masquerade includes install and pruning commands.

## Install

```bash
php artisan masquerade:install
```

Force republishing package files:

```bash
php artisan masquerade:install --force
```

The installer publishes:

- Configuration
- Migration
- Banner view

## Prune Logs

```bash
php artisan masquerade:prune --days=90
```

This deletes audit logs older than the provided number of days.

## Schedule Pruning

Add pruning to `routes/console.php` or your scheduler setup:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('masquerade:prune --days=180')
    ->daily()
    ->withoutOverlapping();
```

## Retention Guidance

Choose retention based on your application's audit and compliance needs.

Common examples:

| App Type | Example Retention |
| --- | --- |
| Small internal app | 90 days |
| Support/admin app | 180 days |
| High-compliance app | 365+ days |

Do not prune logs until you are sure they are no longer needed for security review.
