# 🧩 Middleware

Laravel Masquerade registers four middleware aliases.

## 🚫 `masquerade.block`

Blocks the route while the current session is masquerading.

```php
Route::post('/billing/payment-method', UpdatePaymentMethodController::class)
    ->middleware(['auth', 'masquerade.block']);
```

Good candidates:

- Password changes
- Email changes
- Two-factor authentication changes
- Payment and billing changes
- API token creation
- Account deletion
- Permission and role changes

## ⏱️ `masquerade.duration`

Stops expired sessions automatically before continuing the request.

```php
Route::middleware(['web', 'auth', 'masquerade.duration', 'masquerade.context'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Configure the limit:

```php
'duration' => [
    'enabled' => true,
    'minutes' => 60,
    'allow_extension' => true,
    'max_minutes' => 0,
],
```

## 🎭 `masquerade.required`

Requires an active masquerade session.

```php
Route::get('/support/masquerade-toolbar', SupportToolbarController::class)
    ->middleware(['auth', 'masquerade.required']);
```

Use this for UI fragments, support overlays, or internal endpoints that only make sense during impersonation.


## 🧠 `masquerade.context`

Shares masquerade state on request attributes for support panels, internal headers, or UI macros.

```php
Route::get('/support', SupportDashboardController::class)
    ->middleware(['auth', 'masquerade.context']);

$active = request()->attributes->get('masquerade.active');
$context = request()->attributes->get('masquerade.context');
$session = request()->attributes->get('masquerade.session');
```

## 🧭 Recommended Route Group

```php
Route::middleware(['web', 'auth', 'masquerade.duration'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::middleware('masquerade.block')->group(function (): void {
        Route::post('/password', UpdatePasswordController::class);
        Route::post('/two-factor-authentication', EnableTwoFactorController::class);
        Route::delete('/account', DeleteAccountController::class);
    });
});
```

## 🛡️ Ability Middleware

Use `masquerade.ability` to block a specific sensitive ability while masquerading:

```php
Route::post('/billing', UpdateBillingController::class)
    ->middleware(['auth', 'masquerade.ability:billing.update']);
```

If the ability is configured in `masquerade.abilities.blocked`, the request is rejected and an `ability_blocked` audit log may be written.
