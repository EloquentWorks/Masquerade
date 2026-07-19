<?php

namespace EloquentWorks\Masquerade\Commands;

use EloquentWorks\Masquerade\Models\MasqueradeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Class PruneMasqueradeLogsCommand
 */
final class PruneMasqueradeLogsCommand extends Command
{
    /** @var string The signature of the console command. */
    protected $signature = 'masquerade:prune
        {--days= : Delete logs older than this many days}
        {--dry-run : Count matching logs without deleting them}
        {--force : Run without confirmation}';

    /** @var string The description of the console command. */
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

        // Get the configured masquerade log model class
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Validate that the model class exists and is a string
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $this->components->error('The configured masquerade log model does not exist.');

            // Return a failure status code
            return self::FAILURE;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $query = $modelClass::query()
            ->where('created_at', '<', Carbon::now()->subDays($days));

        // Count the number of logs that match the query
        $count = (int) $query->count();

        // If dry-run option is set, display the count and exit
        if ((bool) $this->option('dry-run')) {
            $this->components->info("{$count} masquerade log(s) would be pruned.");

            // Return a success status code
            return self::SUCCESS;
        }

        // If there are no logs to delete, display a message and exit
        if (! (bool) $this->option('force') && ! $this->confirm("Delete {$count} masquerade log(s)?")) {
            $this->components->warn('Prune cancelled.');

            // Return a success status code
            return self::SUCCESS;
        }

        // Delete the logs and display the number of deleted logs
        $deleted = (int) $query->delete();

        // Display a success message with the number of deleted logs
        $this->components->success("Pruned {$deleted} masquerade log(s).");

        // Return a success status code
        return self::SUCCESS;
    }
}
