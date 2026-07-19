# ✅ Testing

Laravel Masquerade ships with PHPUnit/Testbench configuration for package testing.

## 🧪 Run Package Tests

```bash
composer test
```

## 🔎 Run Static Analysis

```bash
composer analyse
```

## 🎨 Run Formatting

```bash
composer format
```

## ✅ Full Quality Suite

```bash
composer quality
```

## 🧪 Example Application Test

```php
use App\Models\User;
use EloquentWorks\Masquerade\Facades\Masquerade;
use Illuminate\Support\Facades\Auth;

it('allows a support admin to masquerade as a customer', function (): void {
    $admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $customer = User::factory()->create();

    Auth::login($admin);

    Masquerade::start(
        target: $customer,
        reason: 'Support ticket #1042',
    );

    expect(Masquerade::isMasquerading())->toBeTrue();
    expect(Auth::user()->is($customer))->toBeTrue();

    Masquerade::stop();

    expect(Auth::user()->is($admin))->toBeTrue();
});
```

## 🚫 Test Blocked Routes

```php
it('blocks sensitive routes during masquerade', function (): void {
    $this->actingAs($admin);

    Masquerade::start($customer, reason: 'Support ticket');

    $this->post('/billing/payment-method')
        ->assertForbidden();
});
```

## 📜 Test Audit Logs

```php
use EloquentWorks\Masquerade\Models\MasqueradeLog;

expect(MasqueradeLog::query()->where('action', 'started')->exists())->toBeTrue();
```
