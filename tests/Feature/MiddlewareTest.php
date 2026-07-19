<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\TestCase;
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
}
