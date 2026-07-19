<?php

declare(strict_types=1);

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Tests\TestCase;

final class InstallCommandTest extends TestCase
{
    public function test_install_command_runs_successfully(): void
    {
        $this->artisan('masquerade:install')
            ->assertExitCode(0);
    }
}
