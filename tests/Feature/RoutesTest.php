<?php

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
}
