<?php

namespace EloquentWorks\Masquerade\Commands;

use EloquentWorks\Masquerade\Models\MasqueradeLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Command to prune old masquerade audit logs.
 */
final class PruneMasqueradeLogsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'masquerade:prune {--days=90 : Delete logs older than this many days}';

    /**
     * @var string
     */
    protected $description = 'Prune old masquerade audit logs.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        // Get the number of days from the command option, defaulting to 90 if not provided.
        $days = max(1, (int) $this->option('days'));
        $modelClass = config('masquerade.logging.model', MasqueradeLog::class);

        // Validate that the configured model class exists and is a string.
        if (! is_string($modelClass) || ! class_exists($modelClass)) {
            $this->components->error('The configured masquerade log model does not exist.');

            // Return a failure status code if the model class is invalid.
            return self::FAILURE;
        }

        /** @var class-string<MasqueradeLog> $modelClass */
        $deleted = $modelClass::query()
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();

        // Output a success message indicating how many logs were pruned.
        $this->components->success("Pruned {$deleted} masquerade log(s).");

        // Return a success status code after successfully pruning the logs.
        return self::SUCCESS;
    }
}
