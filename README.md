# 🎭 Laravel Masquerade

[![Tests](https://github.com/EloquentWorks/Masquerade/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Masquerade/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/EloquentWorks/Masquerade)](https://github.com/EloquentWorks/Masquerade/releases)
[![License](https://img.shields.io/github/license/EloquentWorks/Masquerade)](LICENSE.md)

A feature-rich user impersonation package for Laravel.

Laravel Masquerade lets trusted administrators, support agents, and staff safely sign in as another user while preserving the original account, enforcing permission checks, blocking sensitive actions, limiting impersonation duration, and recording a full audit trail.

```php
use EloquentWorks\Masquerade\Facades\Masquerade;

Masquerade::start(
    target: $user,
    reason: 'Troubleshooting support ticket #1042',
    metadata: [
        'ticket_id' => 1042,
    ],
);

Masquerade::isMasquerading();

Masquerade::stop();
```

## ✨ Highlights

- Secure session-backed user impersonation
- Original user restoration after masquerade sessions
- Configurable auth guard support
- Configurable user model for built-in routes
- Optional built-in start and stop routes
- Controller-driven impersonation flow
- Model-level permission methods
- Protection against nested impersonation
- Protection against impersonating yourself
- Optional required reason before starting a session
- Session ID regeneration when starting and stopping
- Configurable max session duration
- Automatic expiration middleware
- Sensitive-route blocking middleware
- Middleware for routes that require an active masquerade session
- Full audit logging with actor and target morph relationships
- Reason, IP address, user agent, metadata, start time, and end time logging
- Start, stop, denied, and expired events
- Blade conditional directive and banner directive
- Publishable banner view
- Facade and global helper
- Install and log pruning Artisan commands
- Configurable table name, messages, redirects, and routes
- Laravel Pint, PHPStan/Larastan, PHPUnit, Testbench, and GitHub Actions setup

## 📋 Requirements

| Laravel | PHP | Orchestra Testbench |
| --- | --- | --- |
| 10.x | 8.2+ | 8.x |
| 11.x | 8.2+ | 9.x |
| 12.x | 8.2+ | 10.x |

## 📦 Installation

```bash
composer require eloquent-works/masquerade
php artisan masquerade:install
php artisan migrate
```

The install command publishes the configuration file, migration, and banner view.

Publish only the configuration file:

```bash
php artisan vendor:publish --tag=masquerade-config
```

Publish only the migration:

```bash
php artisan vendor:publish --tag=masquerade-migrations
```

Publish only the banner view:

```bash
php artisan vendor:publish --tag=masquerade-views
```

Force republishing package assets:

```bash
php artisan masquerade:install --force
```

## 👤 Prepare Your User Model

Add `HasMasquerade` to the account model that can perform or receive impersonation.

```php
<?php

namespace App\Models;

use EloquentWorks\Masquerade\Traits\HasMasquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

class User extends AuthenticatableUser
{
    use HasMasquerade;

    public function canMasquerade(Authenticatable $target): bool
    {
        return $this->is_admin && ! $this->is($target);
    }

    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return ! $this->is_owner;
    }
}
```

By default, `canMasquerade()` returns `false`. This means nobody can masquerade until your application explicitly allows it.

## 🎭 Start Masquerading

```php
use EloquentWorks\Masquerade\Facades\Masquerade;

Masquerade::start(
    target: $user,
    reason: 'Troubleshooting checkout issue',
    metadata: [
        'ticket_id' => 1042,
        'source' => 'support-panel',
    ],
);
```

You may also provide a specific impersonator and guard:

```php
Masquerade::start(
    target: $user,
    impersonator: $admin,
    guard: 'web',
    reason: 'QA verification',
);
```

## 🛑 Stop Masquerading

```php
Masquerade::stop();
```

When stopped, Masquerade logs the original user back in and clears the masquerade session state.

## 🔎 Inspect the Current Session

```php
Masquerade::isMasquerading();
Masquerade::hasExpired();
Masquerade::impersonator();
Masquerade::target();
Masquerade::uuid();
Masquerade::context();
```

The helper returns the same manager instance:

```php
masquerade()->isMasquerading();
masquerade()->impersonator();
masquerade()->target();
masquerade()->context();
```

## 🛡️ Protect Routes

Block sensitive actions while masquerading:

```php
use Illuminate\Support\Facades\Route;

Route::post('/billing/payment-method', UpdatePaymentMethodController::class)
    ->middleware(['auth', 'masquerade.block']);
```

Enforce automatic expiration on normal authenticated routes:

```php
Route::middleware(['web', 'auth', 'masquerade.duration'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Require an active masquerade session:

```php
Route::get('/support/masquerade-toolbar', SupportToolbarController::class)
    ->middleware(['auth', 'masquerade.required']);
```

## 🧭 Built-In Routes

When routes are enabled, Masquerade registers:

```text
POST /masquerade/{user}/start
POST /masquerade/stop
```

Start from a Blade form:

```blade
<form method="POST" action="{{ route('masquerade.start', $user) }}">
    @csrf

    <input
        type="hidden"
        name="reason"
        value="Support ticket #1042"
    >

    <input
        type="hidden"
        name="redirect_to"
        value="{{ route('dashboard') }}"
    >

    <button type="submit">
        Masquerade as {{ $user->name }}
    </button>
</form>
```

Stop from a Blade form:

```blade
<form method="POST" action="{{ route('masquerade.stop') }}">
    @csrf

    <button type="submit">
        Return to my account
    </button>
</form>
```

Disable built-in routes if you want to provide your own controller flow:

```php
'routes' => [
    'enabled' => false,
],
```

## 🪪 Permissions

Masquerade checks both sides of the impersonation request.

```php
public function canMasquerade(Authenticatable $target): bool
{
    return $this->hasRole('support') && ! $this->is($target);
}

public function canBeMasqueradedBy(Authenticatable $impersonator): bool
{
    return ! $this->hasRole('owner');
}
```

You may disable model permission methods in the config if your application handles authorization elsewhere:

```php
'permissions' => [
    'use_model_methods' => false,
],
```

## 🧾 Audit Logs

Every started, ended, and expired masquerade session can be recorded in `masquerade_logs`.

```php
use EloquentWorks\Masquerade\Models\MasqueradeLog;

$logs = MasqueradeLog::query()
    ->latest()
    ->get();
```

Each log may include:

- Masquerade UUID
- Action: `started`, `ended`, or `expired`
- Guard name
- Impersonator morph type and ID
- Target morph type and ID
- Reason
- IP address
- User agent
- Metadata
- Started timestamp
- Ended timestamp

Access the actor and target models:

```php
$log->impersonator;
$log->target;
```

## ⏱️ Duration Limits

Masquerade can automatically expire old sessions.

```php
'duration' => [
    'enabled' => true,
    'minutes' => 60,
],
```

Add the duration middleware to routes that should enforce the limit:

```php
Route::middleware(['web', 'auth', 'masquerade.duration'])->group(function (): void {
    // Protected app routes...
});
```

You can also check expiration manually:

```php
if (Masquerade::stopIfExpired()) {
    // The session was expired and stopped.
}
```

## 🚫 Sensitive Route Blocking

Use `masquerade.block` on routes that should never be available while impersonating another account.

Good examples include:

- Billing changes
- Password changes
- Two-factor authentication changes
- Email address changes
- API token creation
- Account deletion
- Admin permission changes

```php
Route::middleware(['auth', 'masquerade.block'])->group(function (): void {
    Route::post('/password', UpdatePasswordController::class);
    Route::post('/two-factor-authentication', EnableTwoFactorController::class);
    Route::delete('/account', DeleteAccountController::class);
});
```

## 🧩 Blade Directives

Show UI only while masquerading:

```blade
@masquerading
    <div class="alert alert-warning">
        You are currently masquerading as another user.
    </div>
@endmasquerading
```

Render the included banner:

```blade
@masqueradeBanner
```

Or include the published view manually:

```blade
@include('masquerade::banner')
```

## 📡 Events

Masquerade dispatches events you can listen for:

```php
EloquentWorks\Masquerade\Events\MasqueradeStarted::class;
EloquentWorks\Masquerade\Events\MasqueradeEnded::class;
EloquentWorks\Masquerade\Events\MasqueradeDenied::class;
EloquentWorks\Masquerade\Events\MasqueradeExpired::class;
```

Example listener registration:

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

## ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=masquerade-config
```

Important options:

```php
return [
    'guard' => null,

    'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),

    'session_key' => 'masquerade',

    'routes' => [
        'enabled' => true,
        'prefix' => 'masquerade',
        'middleware' => ['web', 'auth'],
        'name' => 'masquerade.',
        'start_route_parameter' => 'user',
        'redirect_after_start' => '/',
        'redirect_after_stop' => '/',
    ],

    'permissions' => [
        'use_model_methods' => true,
        'impersonator_method' => 'canMasquerade',
        'target_method' => 'canBeMasqueradedBy',
    ],

    'security' => [
        'allow_nested' => false,
        'allow_same_user' => false,
        'require_reason' => false,
        'regenerate_session_id' => true,
        'logout_on_missing_original_user' => true,
    ],

    'duration' => [
        'enabled' => true,
        'minutes' => 60,
    ],

    'logging' => [
        'enabled' => true,
        'table_name' => 'masquerade_logs',
        'store_ip_address' => true,
        'store_user_agent' => true,
    ],
];
```

## 🧰 Commands

```bash
php artisan masquerade:install
php artisan masquerade:prune
```

Examples:

```bash
php artisan masquerade:install --force

php artisan masquerade:prune --days=90
```

## 🧪 Testing Your App Integration

A simple feature test might look like this:

```php
use App\Models\User;
use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Support\Facades\Auth;

it('allows support admins to masquerade as users', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $user = User::factory()->create();

    Auth::login($admin);

    Masquerade::start($user, reason: 'Support ticket');

    expect(Masquerade::isMasquerading())->toBeTrue();
    expect(Auth::user()->is($user))->toBeTrue();

    Masquerade::stop();

    expect(Auth::user()->is($admin))->toBeTrue();
});
```

## ✅ Quality Checks

```bash
composer validate --strict
composer quality
```

Or run the tools separately:

```bash
composer format
composer analyse
composer test
```

## 🔐 Security

Only allow highly trusted users to masquerade. Always require authorization before starting a session, consider requiring a reason, and block sensitive account, billing, authentication, and destructive routes with `masquerade.block`.

Masquerade regenerates the session ID when starting and stopping by default. Keep that enabled unless your application has a specific reason to disable it.

Security vulnerabilities should be reported privately according to [SECURITY.md](SECURITY.md).

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 📄 License

Laravel Masquerade is open-source software licensed under the [MIT License](LICENSE).
