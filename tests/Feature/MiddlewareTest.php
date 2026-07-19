<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

final class MiddlewareTest extends TestCase
{
    public function test_block_middleware_blocks_while_masquerading(): void
    {
        Route::get('/billing', fn () => 'billing')->middleware(['web', 'auth', 'masquerade.block']);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);
        Masquerade::start($target);

        $this->get('/billing')->assertForbidden();
    }

    public function test_required_middleware_requires_masquerade_session(): void
    {
        Route::get('/support-only', fn () => 'support')->middleware(['web', 'auth', 'masquerade.required']);

        $admin = $this->createUser(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/support-only')
            ->assertForbidden();
    }

    public function test_context_middleware_shares_masquerade_context_on_request_attributes(): void
    {
        Route::get('/context', function (Request $request): array {
            return [
                'active' => $request->attributes->get('masquerade.active'),
                'uuid' => $request->attributes->get('masquerade.context')['uuid'] ?? null,
            ];
        })->middleware(['web', 'auth', 'masquerade.context']);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);
        Masquerade::start($target);

        $this->getJson('/context')
            ->assertOk()
            ->assertJson([
                'active' => true,
                'uuid' => Masquerade::uuid(),
            ]);
    }

    public function test_duration_middleware_expires_session_and_returns_json_response(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        Route::get('/json-area', fn () => ['ok' => true])->middleware(['web', 'auth', 'masquerade.duration']);

        config()->set('masquerade.duration.minutes', 1);

        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        Auth::login($admin);
        Masquerade::start($target);

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:02:00'));

        $this->getJson('/json-area')
            ->assertStatus(419)
            ->assertJson([
                'message' => config('masquerade.messages.expired'),
            ]);

        $this->assertFalse(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($admin));
    }
}
