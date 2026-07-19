# 🎨 Customization

Laravel Masquerade is designed to be used directly or customized around your admin panel.

## Disable Built-In Routes

```php
'routes' => [
    'enabled' => false,
],
```

Then use the `Masquerade` facade in your own controllers.

## Custom Controller Example

```php
use App\Models\User;
use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class SupportMasqueradeController
{
    public function start(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        Masquerade::start(
            target: $user,
            reason: $request->string('reason')->toString(),
            metadata: [
                'ticket_id' => $request->input('ticket_id'),
            ],
        );

        return redirect()->route('dashboard');
    }

    public function stop(): RedirectResponse
    {
        Masquerade::stop();

        return redirect()->route('admin.users.index');
    }
}
```

## Custom Permission Names

```php
'permissions' => [
    'impersonator_method' => 'mayMasqueradeAs',
    'target_method' => 'mayBeMasqueradedBy',
],
```

## Custom Log Model

```php
'logging' => [
    'model' => App\Models\SupportMasqueradeLog::class,
],
```

## Custom Banner

```php
'banner' => [
    'view' => 'admin.partials.support-masquerade-banner',
],
```

## Custom Messages

```php
'messages' => [
    'denied' => 'You cannot access that account.',
    'blocked' => 'This action is disabled while viewing a customer account.',
],
```
