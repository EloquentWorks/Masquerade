# 🛡️ Laravel Exile

[![Tests](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml/badge.svg)](https://github.com/EloquentWorks/Exile/actions/workflows/tests.yml)
[![Latest Release](https://img.shields.io/github/v/release/EloquentWorks/Exile)](https://github.com/EloquentWorks/Exile/releases)
[![License](https://img.shields.io/github/license/EloquentWorks/Exile)](LICENSE)

A comprehensive moderation-enforcement package for Laravel.

Laravel Exile provides account, IP, CIDR network, and device bans; temporary and permanent restrictions; warnings and strike escalation; appeals; evidence management; audit history; middleware; queued notifications; customizable mail templates; and scheduled maintenance.

```php
$user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
    category: 'harassment',
);

$user->strike(
    reason: 'Spam',
    points: 3,
);

$user->restrict(
    RestrictionType::Posting,
    reason: 'Posting cooldown',
);
```

## ✨ Highlights

- Account, exact-IP, CIDR network, device, and combined bans
- Configurable `any` or strict `all` matching for combined bans
- Temporary and permanent enforcement
- Transactional enforcement writes and after-commit side effects
- Login, posting, read-only, and shadow restrictions
- Warnings, strike points, and concurrency-safe escalation
- Appeals with approval, denial, withdrawal, and automatic revocation
- Evidence uploads with SHA-256 integrity checksums
- Moderator attribution, internal notes, metadata, and audit history
- Queued ban notifications with replaceable classes
- Publishable Markdown mail templates
- Middleware, expiration processing, and retention pruning
- Configurable models, table names, categories, and aliases

## 📋 Requirements

| Laravel | PHP | Orchestra Testbench |
| --- | --- | --- |
| 11.15+ | 8.2+ | 9.x |
| 12.x | 8.2+ | 10.x |
| 13.x | 8.3+ | 11.x |

## 📦 Installation

```bash
composer require eloquent-works/exile
php artisan exile:install --migrate
```

Publish the customizable notification templates as part of installation:

```bash
php artisan exile:install --migrate --views
```

Add `Bannable` to the account model:

```php
<?php

namespace App\Models;

use EloquentWorks\Exile\Traits\Bannable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Bannable;
}
```

Configure a dedicated hash key:

```dotenv
EXILE_HASH_KEY=base64:replace-with-a-dedicated-random-key
```

Do not rotate this key casually. Existing IP and device hashes will no longer match after rotation.

## 🛡️ Protect Routes

Block active account, IP, network, and device bans:

```php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'exile'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Block a restricted action:

```php
Route::post('/posts', StorePostController::class)
    ->middleware(['auth', 'exile', 'exile.allowed:posting']);
```

Mark shadow-restricted requests without rejecting them:

```php
Route::post('/comments', StoreCommentController::class)
    ->middleware(['auth', 'exile.shadow']);

$shadowed = (bool) request()
    ->attributes
    ->get('exile.shadowed', false);
```

## 🚫 Account Bans

```php
$ban = $user->ban(
    reason: 'Repeated harassment',
    expiresAt: now()->addDays(7),
    moderator: $moderator,
    category: 'harassment',
    internalNotes: 'Case EX-1042',
    metadata: [
        'case_number' => 'EX-1042',
    ],
);

$user->isBanned();
```

## 🌐 IP, Network, and Device Bans

```php
use EloquentWorks\Exile\Facades\Exile;

Exile::banIp(
    '203.0.113.10',
    reason: 'Automated abuse',
);

Exile::banNetwork(
    '203.0.113.0/24',
    reason: 'Abusive network',
);

Exile::banDevice(
    'application-device-token',
    reason: 'Ban evasion',
);

Exile::banAccountAndIp(
    account: $user,
    ipAddress: request()->ip(),
    reason: 'Ban evasion',
);
```

Register a device observation without storing the raw token:

```php
$user->registerDeviceFingerprint(
    fingerprint: $request->header(
        'X-Device-Fingerprint'
    ),
    ipAddress: $request->ip(),
    label: 'Chrome on Windows',
);
```

## 🔗 Combined-Ban Matching

Exile supports two matching modes:

```php
'security' => [
    'combined_ban_match' => 'any',
],
```

- `any` preserves the original behavior. A combined ban matches when any stored identifier matches.
- `all` requires every identifier belonging to the combined ban type to match.

Strict matching example:

```php
'security' => [
    'combined_ban_match' => 'all',
],
```

With `all`, an `AccountAndIp` ban requires both the correct account and the correct IP address.

## ⚠️ Warnings, Strikes, and Escalation

```php
use EloquentWorks\Exile\Enums\WarningSeverity;

$user->warn(
    reason: 'Please review the community rules.',
    severity: WarningSeverity::High,
);

$user->strike(
    reason: 'Spam',
    points: 3,
    category: 'spam',
);

$user->activeStrikePoints();
```

Escalation runs inside a transaction, locks the affected account row, and reserves each account/threshold combination in `exile_escalations`. This prevents the same threshold from being applied twice by concurrent requests.

## 🔒 Restrictions

```php
use EloquentWorks\Exile\Enums\RestrictionType;

$user->restrict(
    RestrictionType::Posting,
    reason: 'Posting cooldown',
    expiresAt: now()->addDay(),
);

$user->isRestricted(
    RestrictionType::Posting
);

$user->isShadowBanned();
```

## 📨 Appeals

```php
use EloquentWorks\Exile\Enums\AppealStatus;
use EloquentWorks\Exile\Facades\Exile;

$appeal = Exile::submitAppeal(
    ban: $ban,
    appellant: $user,
    message: 'I believe this was issued in error.',
);

Exile::resolveAppeal(
    appeal: $appeal,
    status: AppealStatus::Approved,
    reviewer: $moderator,
    response: 'The appeal was approved.',
);
```

Approving an appeal revokes the related ban.

## 🔐 Evidence Integrity

```php
$evidence = Exile::storeEvidence(
    subject: $ban,
    file: $request->file('evidence'),
    uploadedBy: $moderator,
);

$evidence->checksum_sha256;

$evidence->hasValidChecksum();
```

Newly uploaded evidence receives a SHA-256 checksum. `hasValidChecksum()` recalculates the file hash and securely compares it with the saved value.

## ✉️ Notifications and Mail Templates

Notifications are disabled by default:

```php
'notifications' => [
    'enabled' => true,
    'channels' => ['mail'],
    'issued' => true,
    'revoked' => true,
    'expired' => true,
    'fail_silently' => true,
],
```

The bundled ban notifications are queued and configured to run after database commits. Run a queue worker when notifications are enabled:

```bash
php artisan queue:work
```

Publish the Markdown templates:

```bash
php artisan vendor:publish --tag=exile-views
```

Customize them at:

```text
resources/views/vendor/exile/mail/ban-issued.blade.php
resources/views/vendor/exile/mail/ban-revoked.blade.php
resources/views/vendor/exile/mail/ban-expired.blade.php
```

Notification classes, subjects, views, headings, content, action buttons, date formatting, and channels are configurable.

## 🧰 Commands

```bash
php artisan exile:install
php artisan exile:expire
php artisan exile:prune
```

Examples:

```bash
php artisan exile:install --migrate --views

php artisan exile:expire --chunk=1000

php artisan exile:prune --force --days=365
```

Pruning is disabled by default.

## 📚 Documentation

- [Documentation index](docs/README.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Architecture](docs/architecture.md)
- [Bans](docs/bans.md)
- [IP, network, and device enforcement](docs/identifiers.md)
- [Restrictions](docs/restrictions.md)
- [Warnings, strikes, and escalation](docs/warnings-and-strikes.md)
- [Appeals](docs/appeals.md)
- [Evidence](docs/evidence.md)
- [Middleware](docs/middleware.md)
- [Events, notifications, and audit history](docs/events-notifications-and-audit.md)
- [Commands and scheduling](docs/commands-and-scheduling.md)
- [Customization](docs/customization.md)
- [Security](docs/security.md)
- [Testing](docs/testing.md)

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

Keep `EXILE_HASH_KEY` private. Configure Laravel trusted proxies before relying on request IP addresses behind a proxy or load balancer. Store evidence on a private filesystem disk and protect every download with application authorization.

Security vulnerabilities should be reported privately according to [SECURITY.md](SECURITY.md).

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) and [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## 📄 License

Laravel Exile is open-source software licensed under the [MIT License](LICENSE).
