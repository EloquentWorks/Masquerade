# 🧩 Middleware

Laravel Masquerade registers three middleware aliases.

## `masquerade.block`

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

## `masquerade.duration`

Stops expired sessions automatically before continuing the request.

```php
Route::middleware(['web', 'auth', 'masquerade.duration'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class);
});
```

Configure the limit:

```php
'duration' => [
    'enabled' => true,
    'minutes' => 60,
],
```

## `masquerade.required`

Requires an active masquerade session.

```php
Route::get('/support/masquerade-toolbar', SupportToolbarController::class)
    ->middleware(['auth', 'masquerade.required']);
```

Use this for UI fragments, support overlays, or internal endpoints that only make sense during impersonation.

## Recommended Route Group

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
