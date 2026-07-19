# 🛣️ Routes

Laravel Masquerade can register built-in routes, or you can disable them and create your own.

## 🛣️ Built-In Routes

When routes are enabled, Masquerade registers:

```text
POST /masquerade/{user}/start
POST /masquerade/stop
```

Named routes:

```text
masquerade.start
masquerade.stop
```

## ▶️ Start Form

```blade
<form method="POST" action="{{ route('masquerade.start', $user) }}">
    @csrf

    <input type="hidden" name="reason" value="Support ticket #1042">
    <input type="hidden" name="redirect_to" value="{{ route('dashboard') }}">

    <button type="submit">
        Masquerade as {{ $user->name }}
    </button>
</form>
```

## 🛑 Stop Form

```blade
<form method="POST" action="{{ route('masquerade.stop') }}">
    @csrf

    <button type="submit">
        Return to my account
    </button>
</form>
```

## ⚙️ Route Configuration

```php
'routes' => [
    'enabled' => true,
    'prefix' => 'masquerade',
    'middleware' => ['web', 'auth'],
    'name' => 'masquerade.',
    'start_route_parameter' => 'user',
    'redirect_after_start' => '/',
    'redirect_after_stop' => '/',
],
```

## 🚫 Disable Built-In Routes

```php
'routes' => [
    'enabled' => false,
],
```

Then define your own routes:

```php
use App\Http\Controllers\Admin\MasqueradeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])
    ->prefix('admin/masquerade')
    ->name('admin.masquerade.')
    ->group(function (): void {
        Route::post('/{user}/start', [MasqueradeController::class, 'start'])->name('start');
        Route::post('/stop', [MasqueradeController::class, 'stop'])->name('stop');
    });
```

## ↩️ Redirects

The built-in controller accepts `redirect_to` from the request. If not provided, it uses the configured redirect value.

Avoid accepting arbitrary external redirect URLs from untrusted input. Prefer named application routes.
