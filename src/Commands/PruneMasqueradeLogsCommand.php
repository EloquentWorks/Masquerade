<?php

namespace EloquentWorks\Masquerade\Commands;

use EloquentWorks\Masquerade\Models\MasqueradeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

final class PruneMasqueradeLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masquerade:prune
        {--days= : Delete logs older than this many days}
        {--dry-run : Count matching logs without deleting them}
        {--force : Run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old masquerade audit logs.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Determine the number of days to retain logs
        $days = $this->option('days');
        $days = $days === null || $days === ''
            ? (int) config('masquerade.logging.retention_days', 90)
            : (int) $days;
        $days = max(1, $days);

        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Validate that the configured model class is a valid string and exists
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $this->components->error('The configured masquerade log model does not exist.');

            return self::FAILURE;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $query = $modelClass::query()
            ->where('created_at', '<', Carbon::now()->subDays($days));

        // Count the number of logs that would be deleted
        $count = (int) $query->count();

        // If there are no logs to delete, inform the user and exit
        if ((bool) $this->option('dry-run')) {
            $this->components->info("{$count} masquerade log(s) would be pruned.");

            return self::SUCCESS;
        }

        // If there are no logs to delete, inform the user and exit
        if (! (bool) $this->option('force') && ! $this->confirm("Delete {$count} masquerade log(s)?")) {
            $this->components->warn('Prune cancelled.');

            return self::SUCCESS;
        }

        // Delete the logs and inform the user of the number deleted
        $deleted = (int) $query->delete();

        // If no logs were deleted, inform the user and exit
        if ($deleted === 0) {
            $this->components->info('No masquerade logs were pruned.');

            return self::SUCCESS;
        }

        $this->components->success("Pruned {$deleted} masquerade log(s).");

        return self::SUCCESS;
    }
}
