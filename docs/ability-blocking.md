# 🛡️ Ability Blocking

Ability blocking lets applications block sensitive actions while masquerading.

This is useful when route-level blocking is too broad or when sensitive work happens in a controller, policy, service, or Livewire component.

## ⚙️ Configuration

```php
'abilities' => [
    'blocked' => [
        'billing.update',
        'password.change',
        'two-factor.update',
        'api-tokens.create',
        'account.delete',
        'payment-methods.update',
    ],
    'log_blocked' => true,
],
```

## ✅ Check an Ability

```php
if (Masquerade::blocksAbility('billing.update')) {
    abort(403);
}
```

## 🚫 Assert an Ability

```php
Masquerade::assertAbilityAllowed('billing.update');
```

If the ability is blocked, a `MasqueradeAbilityBlockedException` is thrown.

## 🧩 Middleware

```php
Route::post('/billing', UpdateBillingController::class)
    ->middleware(['auth', 'masquerade.ability:billing.update']);
```

## 📜 Audit Logs

Blocked abilities write an `ability_blocked` audit log action when `log_blocked` is enabled.
