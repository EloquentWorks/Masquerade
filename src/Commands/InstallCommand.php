<?php

namespace EloquentWorks\Masquerade\Commands;

use Illuminate\Console\Command;

/**
 * Class InstallCommand
 */
final class InstallCommand extends Command
{
    /** @var string The signature of the console command. */
    protected $signature = 'masquerade:install {--force : Overwrite published files}';

    /** @var string The description of the console command. */
    protected $description = 'Install Laravel Masquerade.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Display an informational message to the console
        $this->components->info('Installing Laravel Masquerade...');

        // Publish the configuration file
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-config',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the migration files
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the view files
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-views',
            '--force' => (bool) $this->option('force'),
        ]);

        // Display a success message to the console
        $this->components->success('Laravel Masquerade installed successfully.');
        $this->components->info('Next: run php artisan migrate and add the HasMasquerade trait to your User model.');

        // Return a success exit code
        return self::SUCCESS;
    }
}
