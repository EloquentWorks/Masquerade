# 📦 Installation

Install Laravel Masquerade with Composer:

```bash
composer require eloquent-works/masquerade
```

Run the installer:

```bash
php artisan masquerade:install
```

Run the published migration:

```bash
php artisan migrate
```

## Publish Assets Manually

Publish the config file:

```bash
php artisan vendor:publish --tag=masquerade-config
```

Publish the migration:

```bash
php artisan vendor:publish --tag=masquerade-migrations
```

Publish the banner view:

```bash
php artisan vendor:publish --tag=masquerade-views
```

Force republishing:

```bash
php artisan masquerade:install --force
```

## Add the Trait

Add `HasMasquerade` to the user model that can perform or receive masquerade sessions.

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

By default, nobody can masquerade until your application explicitly allows it.

## Verify Installation

```bash
php artisan route:list --name=masquerade
php artisan migrate:status
```

You should see the `masquerade.start` and `masquerade.stop` routes if built-in routes are enabled.
