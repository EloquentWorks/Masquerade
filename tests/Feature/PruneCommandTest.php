<?php

namespace EloquentWorks\Masquerade\Tests\Feature;

use EloquentWorks\Masquerade\Enums\MasqueradeAction;
use EloquentWorks\Masquerade\Models\MasqueradeLog;
use EloquentWorks\Masquerade\Tests\TestCase;

final class PruneCommandTest extends TestCase
{
    public function test_prune_command_can_run_as_dry_run_without_deleting_logs(): void
    {
        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000001',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        $this->artisan('masquerade:prune', [
            '--days' => 90,
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, MasqueradeLog::query()->count());
    }

    public function test_prune_command_deletes_old_logs_when_forced(): void
    {
        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000001',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(100),
            'updated_at' => now()->subDays(100),
        ]);

        MasqueradeLog::query()->create([
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000002',
            'action' => MasqueradeAction::Ended->value,
            'guard' => 'web',
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        $this->artisan('masquerade:prune', [
            '--days' => 90,
            '--force' => true,
        ])->assertExitCode(0);

        $this->assertSame(1, MasqueradeLog::query()->count());
        $this->assertDatabaseHas('masquerade_logs', [
            'masquerade_uuid' => '00000000-0000-0000-0000-000000000002',
        ]);
    }
}
