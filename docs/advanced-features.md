# 🚀 Advanced Features

Laravel Masquerade includes extra controls for production support panels, admin dashboards, and internal tools.

## 🧭 Session DTO

Use the typed session snapshot when you want a stable object for toolbars or internal UI.

```php
$session = Masquerade::session();

$session?->uuid;
$session?->impersonator;
$session?->target;
$session?->remainingSeconds;
```

## 🧠 Context Array

Use `context()` when you want a simple array for views, request attributes, or JSON responses.

```php
$context = Masquerade::context();
```

## 🏷️ Metadata Updates

Attach support ticket details, case numbers, or temporary UI state to the active session.

```php
Masquerade::updateMetadata([
    'ticket_id' => 1042,
    'priority' => 'high',
]);
```

Replace metadata instead of merging it:

```php
Masquerade::updateMetadata([
    'case' => 'SUP-1042',
], merge: false);
```

## ⏱️ Session Extension

Extend a session when a real support workflow takes longer than expected.

```php
Masquerade::extend(15, reason: 'Support call is still active');
```

You can disable extension or cap total duration:

```php
'duration' => [
    'allow_extension' => true,
    'max_minutes' => 120,
],
```

## 🚫 Denied Attempt Logging

Denied attempts can be logged for audit and alerting.

```php
'logging' => [
    'log_denied_attempts' => true,
],
```

Query denied attempts:

```php
MasqueradeLog::query()->denied()->latest()->get();
```

## 🔎 Log Scopes

Masquerade logs include expressive scopes for common audit views.

```php
MasqueradeLog::query()->started()->get();
MasqueradeLog::query()->ended()->get();
MasqueradeLog::query()->expired()->get();
MasqueradeLog::query()->extended()->get();
MasqueradeLog::query()->forMasqueradeUuid($uuid)->get();
MasqueradeLog::query()->forImpersonator($admin)->get();
MasqueradeLog::query()->forTarget($user)->get();
```

## 🧩 Request Context Middleware

Attach masquerade state to the current request.

```php
Route::middleware(['web', 'auth', 'masquerade.context'])->group(function (): void {
    Route::get('/support', SupportDashboardController::class);
});
```

Inside controllers or views:

```php
request()->attributes->get('masquerade.active');
request()->attributes->get('masquerade.context');
request()->attributes->get('masquerade.session');
```
