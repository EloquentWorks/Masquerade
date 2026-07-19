<?php

namespace EloquentWorks\Masquerade\Commands;

use Illuminate\Console\Command;

/**
 * Class InstallCommand
 *
 * This command is responsible for installing Laravel Masquerade by publishing its configuration,
 * migrations, and views. It provides an option to force overwrite existing files.
 */
final class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'masquerade:install {--force : Overwrite published files}';

    /**
     * @var string
     */
    protected $description = 'Install Laravel Masquerade.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Inform the user that the installation process has started
        $this->components->info('Installing Laravel Masquerade...');

        // Publish the configuration file for Laravel Masquerade
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-config',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the migration files for Laravel Masquerade
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the view files for Laravel Masquerade
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-views',
            '--force' => (bool) $this->option('force'),
        ]);

        // Inform the user that the installation process has completed successfully
        $this->components->success('Laravel Masquerade installed successfully.');
        $this->components->info('Next: run php artisan migrate and add the HasMasquerade trait to your User model.');

        // Return a success status code to indicate that the command executed successfully
        return self::SUCCESS;
    }
}
