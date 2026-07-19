<?php

declare(strict_types=1);

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\TestCase;

final class RoutesTest extends TestCase
{
    public function test_built_in_start_and_stop_routes_work(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), ['reason' => 'Support'])
            ->assertRedirect('/');

        $this->assertTrue(Masquerade::isMasquerading());

        $this->post(route('masquerade.stop'))
            ->assertRedirect('/');

        $this->assertFalse(Masquerade::isMasquerading());
    }

    public function test_start_route_allows_safe_relative_redirects(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), [
                'reason' => 'Support',
                'redirect_to' => '/support/users/'.$target->id,
            ])
            ->assertRedirect('/support/users/'.$target->id);
    }

    public function test_start_route_rejects_untrusted_external_redirects(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), [
                'reason' => 'Support',
                'redirect_to' => 'https://evil.example/steal-session',
            ])
            ->assertRedirect('/');
    }

    public function test_stop_route_accepts_safe_redirect(): void
    {
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), ['reason' => 'Support']);

        $this->post(route('masquerade.stop'), [
            'redirect_to' => '/admin/users',
        ])->assertRedirect('/admin/users');
    }
}
