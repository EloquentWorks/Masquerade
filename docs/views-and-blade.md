# 🖼️ Views and Blade

Laravel Masquerade ships with a small banner view and Blade helpers.

## 🎭 Conditional Directive

Use `@masquerading` to show UI only during an active session.

```blade
@masquerading
    <div class="alert alert-warning">
        You are currently masquerading as another user.
    </div>
@endmasquerading
```

## 🖼️ Banner Directive

Render the package banner:

```blade
@masqueradeBanner
```

The directive respects this config value:

```php
'banner' => [
    'enabled' => true,
    'view' => 'masquerade::banner',
],
```

## 🧩 Manual Include

```blade
@include('masquerade::banner')
```

## 📤 Publish the Banner View

```bash
php artisan vendor:publish --tag=masquerade-views
```

Published path:

```text
resources/views/vendor/masquerade/banner.blade.php
```

## 🎨 Custom Banner View

Create your own view and point the config to it:

```php
'banner' => [
    'enabled' => true,
    'view' => 'admin.partials.masquerade-banner',
],
```

## 🧱 Example Layout Usage

```blade
<body>
    @masqueradeBanner

    {{ $slot }}
</body>
```
