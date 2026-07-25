<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use EloquentWorks\Masquerade\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/**
 * Test cases for the masquerade middleware.
 */
final class MiddlewareTest extends TestCase
{
    public function test_block_middleware_blocks_while_masquerading(): void
    {
        // Define a route that is blocked while masquerading
        Route::get('/billing', fn () => 'billing')->middleware(['web', 'auth', 'masquerade.block']);

        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin and start masquerading as the target user
        Auth::login($admin);
        Masquerade::start($target);

        // Attempt to access the blocked route while masquerading
        $this->get('/billing')->assertForbidden();
    }

    public function test_required_middleware_requires_masquerade_session(): void
    {
        // Define a route that requires a masquerade session
        Route::get('/support-only', fn () => 'support')->middleware(['web', 'auth', 'masquerade.required']);

        // Create an admin user
        $admin = $this->createUser(['is_admin' => true]);

        // Log in as the admin without starting a masquerade session
        $this->actingAs($admin)
            ->get('/support-only')
            ->assertForbidden();
    }

    public function test_context_middleware_shares_masquerade_context_on_request_attributes(): void
    {
        // Define a route that returns the masquerade context
        Route::get('/context', function (Request $request): array {
            return [
                'active' => $request->attributes->get('masquerade.active'),
                'uuid' => $request->attributes->get('masquerade.context')['uuid'] ?? null,
            ];
        })->middleware(['web', 'auth', 'masquerade.context']);

        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin and start masquerading as the target user
        Auth::login($admin);
        Masquerade::start($target);

        // Assert that the masquerade context is shared in the request attributes
        $this->getJson('/context')
            ->assertOk()
            ->assertJson([
                'active' => true,
                'uuid' => Masquerade::uuid(),
            ]);
    }

    public function test_duration_middleware_expires_session_and_returns_json_response(): void
    {
        // Set the current time to a fixed point for testing
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:00:00'));

        // Define a route that requires a masquerade session with a duration limit
        Route::get('/json-area', fn () => ['ok' => true])->middleware(['web', 'auth', 'masquerade.duration']);

        // Set the masquerade duration to 1 minute for testing
        config()->set('masquerade.duration.minutes', 1);

        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Log in as the admin and start masquerading as the target user
        Auth::login($admin);
        Masquerade::start($target);

        // Advance time by 2 minutes to exceed the masquerade duration
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-01-01 12:02:00'));

        // Attempt to access the route that requires a masquerade session, expecting it to expire
        $this->getJson('/json-area')
            ->assertStatus(419)
            ->assertJson([
                'message' => config('masquerade.messages.expired'),
            ]);

        // Assert that the masquerade session has expired and the current user is the original admin
        $this->assertFalse(Masquerade::isMasquerading());
        $currentUser = Auth::user();
        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertTrue($currentUser->is($admin));
    }
}
