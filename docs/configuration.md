# ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=masquerade-config
```

The file is published to:

```text
config/masquerade.php
```

## 🛡️ Auth Guard

```php
'guard' => null,
```

When `null`, Masquerade uses Laravel's default auth guard. Set this to `web` or another session guard when your application has multiple guards.

## 👤 User Model

```php
'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),
```

This model is used by the built-in controller routes when resolving the target user from the route parameter.

## 🔑 Session Key

```php
'session_key' => 'masquerade',
```

Masquerade stores its context under this session key. Change it only if your application already uses the same key.

## 🛣️ Routes

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'masquerade',
    'middleware' => ['web', 'auth'],
    'name' => 'masquerade.',
    'start_route_parameter' => 'user',
    'redirect_after_start' => '/',
    'redirect_after_stop' => '/',
],
```

Disable routes when you want to build a fully custom controller flow:

```php
'routes' => [
    'enabled' => false,
],
```

## 🔐 Permissions

```php
'permissions' => [
    'use_model_methods' => true,
    'impersonator_method' => 'canMasquerade',
    'target_method' => 'canBeMasqueradedBy',
],
```

When enabled, Masquerade calls both model methods before starting a session.

## 🛡️ Security

```php
'security' => [
    'allow_nested' => false,
    'allow_same_user' => false,
    'require_reason' => false,
    'regenerate_session_id' => true,
    'logout_on_missing_original_user' => true,
],
```

Recommended production values:

```php
'security' => [
    'allow_nested' => false,
    'allow_same_user' => false,
    'require_reason' => true,
    'regenerate_session_id' => true,
    'logout_on_missing_original_user' => true,
],
```

## ⏱️ Duration Limit

```php
'duration' => [
    'enabled' => true,
    'minutes' => 60,
],
```

Use `masquerade.duration` middleware to enforce this limit during requests.

## 🧾 Logging

```php
'logging' => [
    'enabled' => true,
    'model' => EloquentWorks\Masquerade\Models\MasqueradeLog::class,
    'table_name' => 'masquerade_logs',
    'store_ip_address' => true,
    'store_user_agent' => true,
],
```

Disable IP or user-agent storage if your application has stricter privacy requirements.

## 🖼️ Banner

```php
'banner' => [
    'enabled' => true,
    'view' => 'masquerade::banner',
],
```

Publish the view if you want to customize the UI.

## 💬 Messages

```php
'messages' => [
    'started' => 'You are now masquerading as another user.',
    'stopped' => 'You have returned to your original account.',
    'denied' => 'You are not allowed to masquerade as this user.',
    'expired' => 'Your masquerade session has expired.',
    'blocked' => 'This action is blocked while masquerading.',
],
```
