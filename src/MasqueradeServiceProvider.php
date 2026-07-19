<?php

namespace EloquentWorks\Masquerade;

use EloquentWorks\Masquerade\Commands\InstallCommand;
use EloquentWorks\Masquerade\Commands\PruneMasqueradeLogsCommand;
use EloquentWorks\Masquerade\Http\Middleware\BlockMasquerade;
use EloquentWorks\Masquerade\Http\Middleware\EnforceMasqueradeDuration;
use EloquentWorks\Masquerade\Http\Middleware\RequireMasquerade;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the Masquerade package.
 */
final class MasqueradeServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     */
    public function register(): void
    {
        // Merge the package configuration with the application's configuration
        $this->mergeConfigFrom(__DIR__.'/../config/masquerade.php', 'masquerade');

        // Register the MasqueradeManager as a singleton in the service container
        $this->app->singleton(MasqueradeManager::class, function ($app): MasqueradeManager {
            return new MasqueradeManager(
                auth: $app['auth'],
                session: $app['session.store'],
                events: $app['events'],
                request: $app['request'],
            );
        });

        // Create an alias for the MasqueradeManager to allow for easy access via the facade
        $this->app->alias(MasqueradeManager::class, 'masquerade');
    }

    /**
     * Bootstrap package services.
     *
     * @param  Router  $router
     * @return void
     */
    public function boot(Router $router): void
    {
        // Load the package's views and make them available under the 'masquerade' namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'masquerade');

        // Load the package's routes if they are enabled in the configuration
        if ((bool) config('masquerade.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // Register middleware aliases for masquerade functionality
        $router->aliasMiddleware('masquerade.block', BlockMasquerade::class);
        $router->aliasMiddleware('masquerade.duration', EnforceMasqueradeDuration::class);
        $router->aliasMiddleware('masquerade.required', RequireMasquerade::class);

        // Register a Blade directive to check if the user is currently masquerading
        Blade::if('masquerading', function (): bool {
            return app(MasqueradeManager::class)->isMasquerading();
        });

        // Register a Blade directive to display the masquerade banner if enabled in the configuration
        Blade::directive('masqueradeBanner', function (): string {
            return "<?php if (config('masquerade.banner.enabled', true)) { echo view(config('masquerade.banner.view', 'masquerade::banner'))->render(); } ?>";
        });

        // Publish configuration, migrations, views, and register console commands if running in the console
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish the package's configuration file to the application's config directory
        $this->publishes([
            __DIR__.'/../config/masquerade.php' => config_path('masquerade.php'),
        ], 'masquerade-config');

        // Publish the package's migration file to the application's database/migrations directory with a timestamped filename
        $this->publishes([
            __DIR__.'/../database/migrations/create_masquerade_logs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_masquerade_logs_table.php'),
        ], 'masquerade-migrations');

        // Publish the package's view file to the application's resources/views/vendor/masquerade directory
        $this->publishes([
            __DIR__.'/../resources/views/banner.blade.php' => resource_path('views/vendor/masquerade/banner.blade.php'),
        ], 'masquerade-views');

        // Register console commands provided by the package
        $this->commands([
            InstallCommand::class,
            PruneMasqueradeLogsCommand::class,
        ]);
    }
}
