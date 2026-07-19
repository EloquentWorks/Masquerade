# 📜 Audit Logs

Masquerade can record every started, ended, and expired session in `masquerade_logs`.

## Migration Columns

The default migration creates:

```text
id
masquerade_uuid
action
guard
impersonator_type
impersonator_id
target_type
target_id
reason
ip_address
user_agent
metadata
started_at
ended_at
created_at
updated_at
```

## Query Logs

```php
use EloquentWorks\Masquerade\Models\MasqueradeLog;

$logs = MasqueradeLog::query()
    ->latest()
    ->paginate();
```

## Find a Session by UUID

```php
$logs = MasqueradeLog::query()
    ->where('masquerade_uuid', $uuid)
    ->orderBy('created_at')
    ->get();
```

A normal session usually has a `started` row and an `ended` row. An expired session has a `started` row and an `expired` row.

## Access the Impersonator and Target

```php
$log->impersonator;
$log->target;
```

These are polymorphic relationships, so your application can support custom authenticatable model classes.

## Metadata

Pass metadata when starting a session:

```php
Masquerade::start(
    target: $user,
    reason: 'Support request',
    metadata: [
        'ticket_id' => 1042,
        'admin_panel' => 'nova',
    ],
);
```

## Privacy Controls

Disable IP or user-agent storage in config:

```php
'logging' => [
    'store_ip_address' => false,
    'store_user_agent' => false,
],
```

## Custom Log Model

```php
'logging' => [
    'model' => App\Models\MasqueradeLog::class,
],
```

Your custom model should extend the package model or provide compatible columns and relationships.
