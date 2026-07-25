<?php

namespace EloquentWorks\Masquerade\Commands;

use Illuminate\Console\Command;

/**
 * Command to install Laravel Masquerade.
 */
final class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'masquerade:install {--force : Overwrite published files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Laravel Masquerade.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->components->info('Installing Laravel Masquerade...');

        // Publish the configuration
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-config',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the migrations
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-migrations',
            '--force' => (bool) $this->option('force'),
        ]);

        // Publish the views
        $this->callSilent('vendor:publish', [
            '--tag' => 'masquerade-views',
            '--force' => (bool) $this->option('force'),
        ]);

        // Output the successfully installed message and next steps
        $this->components->success('Laravel Masquerade installed successfully.');
        $this->components->info('Next: run php artisan migrate and add the HasMasquerade trait to your User model.');

        return self::SUCCESS;
    }
}
