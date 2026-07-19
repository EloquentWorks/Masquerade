# 🧱 Architecture

Laravel Masquerade is built around a small manager class, Laravel's auth/session services, middleware, events, and an optional audit log model.

## Main Components

| Component | Purpose |
| --- | --- |
| `MasqueradeManager` | Starts, stops, checks, expires, and records masquerade sessions. |
| `MasqueradeServiceProvider` | Registers bindings, aliases, routes, views, middleware, commands, and Blade directives. |
| `MasqueradeLog` | Stores started, ended, and expired audit log rows. |
| `HasMasquerade` | Adds default permission hooks to your user model. |
| Middleware | Blocks sensitive routes, enforces duration limits, or requires active masquerade. |
| Events | Announces started, ended, denied, and expired lifecycle actions. |

## Session Context

The manager stores session values under the configured session key:

```text
masquerade.active
masquerade.uuid
masquerade.guard
masquerade.impersonator_id
masquerade.impersonator_type
masquerade.target_id
masquerade.target_type
masquerade.reason
masquerade.metadata
masquerade.started_at
masquerade.expires_at
```

The session context lets Masquerade restore the original account after the support/admin user stops masquerading.

## Start Flow

1. Resolve the guard.
2. Resolve the current authenticated impersonator unless one is provided.
3. Reject nested sessions unless enabled.
4. Require a reason if configured.
5. Run permission checks.
6. Store session context.
7. Write a `started` audit log row.
8. Log in as the target user.
9. Regenerate the session ID if configured.
10. Dispatch `MasqueradeStarted`.

## Stop Flow

1. Resolve the guard from the session or config.
2. Load the impersonator and target models.
3. Log back in as the original impersonator when possible.
4. Write an `ended` or `expired` audit log row.
5. Clear the masquerade session context.
6. Regenerate the session ID if configured.
7. Dispatch `MasqueradeEnded` or `MasqueradeExpired`.

## Storage Model

Masquerade does not create a separate live-session table. Active state lives in the Laravel session. The database log table is used for audit history.
