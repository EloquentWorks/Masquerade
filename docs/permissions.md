# 🔐 Permissions

Masquerade performs permission checks before a session starts.

## Add Permission Methods

Add `HasMasquerade` to your user model and override the default permission methods.

```php
use EloquentWorks\Masquerade\Traits\HasMasquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

class User extends AuthenticatableUser
{
    use HasMasquerade;

    public function canMasquerade(Authenticatable $target): bool
    {
        return $this->hasRole('support') && ! $this->is($target);
    }

    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return ! $this->hasRole('owner');
    }
}
```

## Two-Sided Authorization

Masquerade checks both users:

- The impersonator must be allowed to masquerade as the target.
- The target must be allowed to be masqueraded by the impersonator.

This makes it easy to protect owners, super-admins, service accounts, or high-risk accounts.

## Default Behavior

The included trait denies impersonation by default:

```php
public function canMasquerade(Authenticatable $target): bool
{
    return false;
}

public function canBeMasqueradedBy(Authenticatable $impersonator): bool
{
    return true;
}
```

This safe default means your app has to opt in.

## Custom Method Names

Change the method names in `config/masquerade.php`:

```php
'permissions' => [
    'use_model_methods' => true,
    'impersonator_method' => 'mayMasqueradeAs',
    'target_method' => 'mayBeMasqueradedBy',
],
```

Then implement those methods on your model.

## Disable Model Methods

If your app handles authorization elsewhere, you can disable model methods:

```php
'permissions' => [
    'use_model_methods' => false,
],
```

Only do this when your controller, policy, gate, or admin panel performs equivalent checks.

## Policy Example

```php
use EloquentWorks\Masquerade\Facades\Masquerade;

public function start(User $admin, User $target): RedirectResponse
{
    $this->authorize('masqueradeAs', $target);

    Masquerade::start(
        target: $target,
        reason: request('reason'),
    );

    return redirect()->route('dashboard');
}
```
