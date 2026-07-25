<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;

/**
 * Test case for the prune command.
 */
final class PruneCommandTest extends TestCase
{
    public function test_prune_command_can_run_as_dry_run_without_deleting_logs(): void
    {
        // Create a masquerade log entry that is older than 90 days
        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000001',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        // Create a masquerade log entry that is newer than 90 days
        $this->artisan('masquerade:prune', [
            '--days' => 90,
            '--dry-run' => true,
        ])->assertExitCode(0);

        // Assert that the log entries are still present in the database
        $this->assertSame(1, MasqueradeLog::query()->count());
    }

    public function test_prune_command_deletes_old_logs_when_forced(): void
    {
        // Create a masquerade log entry that is older than 90 days
        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000001',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        // Create a masquerade log entry that is newer than 90 days
        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000002',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Run the prune command with the --force option to delete old logs
        $this->artisan('masquerade:prune', [
            '--days' => 90,
            '--force' => true,
        ])->assertExitCode(0);

        // Assert that only the newer log entry remains in the database
        $this->assertSame(1, MasqueradeLog::query()->count());
        $this->assertDatabaseHas('masquerade_logs', [
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000002',
        ]);
    }
}
