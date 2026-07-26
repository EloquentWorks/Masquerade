# 🧰 Commands and Scheduling

Laravel Masquerade includes install and pruning commands.

## 📦 Install

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

## 🧹 Prune Logs

```bash
php artisan masquerade:prune --days=90 --dry-run
php artisan masquerade:prune --days=90 --force
```

`--dry-run` counts matching logs without deleting them. `--force` skips confirmation. Without `--days`, the command uses `masquerade.logging.retention_days`.

## ⏰ Schedule Pruning

Add pruning to `routes/console.php` or your scheduler setup:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('masquerade:prune --days=180 --force')
    ->daily()
    ->withoutOverlapping();
```

## 🗓️ Retention Guidance

Choose retention based on your application's audit and compliance needs.

Common examples:

| App Type | Example Retention |
| --- | --- |
| Small internal app | 90 days |
| Support/admin app | 180 days |
| High-compliance app | 365+ days |

Do not prune logs until you are sure they are no longer needed for security review.

## 📤 Export Audit Logs

```bash
php artisan masquerade:export --format=csv
php artisan masquerade:export --format=json
```

Filter by date range:

```bash
php artisan masquerade:export --from=2026-07-01 --to=2026-07-31
```

Filter by UUID:

```bash
php artisan masquerade:export --uuid=00000000-0000-0000-0000-000000000000
```
