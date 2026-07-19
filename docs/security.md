# 🛡️ Security

Masquerading is powerful and should be treated as privileged access.

## Recommended Defaults

```php
'security' => [
    'allow_nested' => false,
    'allow_same_user' => false,
    'require_reason' => true,
    'regenerate_session_id' => true,
    'logout_on_missing_original_user' => true,
],
```

## Require Strong Authorization

Only trusted roles should be able to start sessions.

```php
public function canMasquerade(Authenticatable $target): bool
{
    return $this->hasRole('support-admin') && ! $this->is($target);
}
```

Protect owners and highly privileged accounts:

```php
public function canBeMasqueradedBy(Authenticatable $impersonator): bool
{
    return ! $this->hasAnyRole(['owner', 'super-admin']);
}
```

## Block Sensitive Actions

Use `masquerade.block` on dangerous routes:

```php
Route::middleware(['auth', 'masquerade.block'])->group(function (): void {
    Route::post('/password', UpdatePasswordController::class);
    Route::post('/two-factor-authentication', EnableTwoFactorController::class);
    Route::post('/billing/payment-method', UpdatePaymentMethodController::class);
    Route::delete('/account', DeleteAccountController::class);
});
```

## Require Reasons

Reasons make audits more useful.

```php
'security' => [
    'require_reason' => true,
],
```

## Keep Duration Limits Enabled

```php
'duration' => [
    'enabled' => true,
    'minutes' => 60,
],
```

Add the middleware to your authenticated app routes:

```php
Route::middleware(['web', 'auth', 'masquerade.duration'])->group(function (): void {
    // Application routes...
});
```

## Session Regeneration

Keep `regenerate_session_id` enabled to reduce session fixation risk when switching identities.

## Audit Everything

Keep logging enabled for production support/admin tools.

```php
'logging' => [
    'enabled' => true,
    'store_ip_address' => true,
    'store_user_agent' => true,
],
```

Review logs regularly and alert on unusual activity.

## Avoid External Redirects

If you accept `redirect_to` values in custom controllers, restrict them to internal routes.
