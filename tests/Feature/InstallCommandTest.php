<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Tests\TestCase;

/**
 * Test case for the masquerade install command.
 */
final class InstallCommandTest extends TestCase
{
    public function test_install_command_runs_successfully(): void
    {
        // Run the masquerade install command and assert that it exits with code 0 (success).
        $this->artisan('masquerade:install')
            ->assertExitCode(0);
    }
}
