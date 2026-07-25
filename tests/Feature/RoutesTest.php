<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Facades\Masquerade;
use EloquentWorks\Masquerade\Tests\TestCase;

/**
 * Test cases for the built-in masquerade routes.
 */
final class RoutesTest extends TestCase
{
    public function test_built_in_start_and_stop_routes_work(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Start masquerading as the target user
        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), ['reason' => 'Support'])
            ->assertRedirect('/');

        // Assert that the current user is now masquerading
        $this->assertTrue(Masquerade::isMasquerading());

        // Stop masquerading
        $this->post(route('masquerade.stop'))
            ->assertRedirect('/');

        // Assert that the current user is no longer masquerading
        $this->assertFalse(Masquerade::isMasquerading());
    }

    public function test_start_route_allows_safe_relative_redirects(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Start masquerading as the target user with a safe relative redirect
        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), [
                'reason' => 'Support',
                'redirect_to' => '/support/users/'.$target->id,
            ])
            // Assert that the response redirects to the specified safe relative URL
            ->assertRedirect('/support/users/'.$target->id);
    }

    public function test_start_route_rejects_untrusted_external_redirects(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Attempt to start masquerading with an untrusted external redirect
        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), [
                'reason' => 'Support',
                'redirect_to' => 'https://evil.example/steal-session',
            ])
            // Assert that the response redirects to the default route instead of the untrusted URL
            ->assertRedirect('/');
    }

    public function test_stop_route_accepts_safe_redirect(): void
    {
        // Create an admin user and a target user
        $admin = $this->createUser(['is_admin' => true]);
        $target = $this->createUser();

        // Start masquerading as the target user
        $this->actingAs($admin)
            ->post(route('masquerade.start', $target), ['reason' => 'Support']);

        // Stop masquerading with a safe relative redirect
        $this->post(route('masquerade.stop'), [
            'redirect_to' => '/admin/users',
        ])->assertRedirect('/admin/users');
    }
}
