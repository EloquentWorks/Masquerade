# ⏱️ Extension Limits

Laravel Masquerade v1.1.0 adds explicit extension controls.

## ⚙️ Configuration

```php
'extensions' => [
    'enabled' => true,
    'max_extensions' => 2,
    'max_minutes_per_extension' => 15,
    'require_reason' => false,
],
```

## ➕ Extend a Session

```php
Masquerade::extend(
    minutes: 15,
    reason: 'Support call is still active.',
);
```

## 🔢 Extension Count

```php
Masquerade::extensionCount();
```

## 🔐 Required Reason

```php
'extensions' => [
    'require_reason' => true,
],
```
