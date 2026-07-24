<?php

namespace EloquentWorks\Masquerade;

use EloquentWorks\Masquerade\Commands\ExportMasqueradeLogsCommand;
use EloquentWorks\Masquerade\Commands\InstallCommand;
use EloquentWorks\Masquerade\Commands\PruneMasqueradeLogsCommand;
use EloquentWorks\Masquerade\Http\Middleware\BlockMasquerade;
use EloquentWorks\Masquerade\Http\Middleware\BlockMasqueradeAbility;
use EloquentWorks\Masquerade\Http\Middleware\EnforceMasqueradeDuration;
use EloquentWorks\Masquerade\Http\Middleware\RequireMasquerade;
use EloquentWorks\Masquerade\Http\Middleware\ShareMasqueradeContext;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Class MasqueradeServiceProvider
 *
 * @package EloquentWorks\Masquerade
 */
final class MasqueradeServiceProvider extends ServiceProvider
{
    /**
     * Register package services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/masquerade.php', 'masquerade');

        $this->app->singleton(MasqueradeManager::class, function ($app): MasqueradeManager {
            return new MasqueradeManager(
                auth: $app['auth'],
                session: $app['session.store'],
                events: $app['events'],
                request: $app['request'],
            );
        });

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
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'masquerade');

        // Load routes if enabled in the configuration
        if ((bool) config('masquerade.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        // Load middleware if enabled in the configuration
        $router->aliasMiddleware('masquerade.block', BlockMasquerade::class);
        $router->aliasMiddleware('masquerade.duration', EnforceMasqueradeDuration::class);
        $router->aliasMiddleware('masquerade.required', RequireMasquerade::class);
        $router->aliasMiddleware('masquerade.context', ShareMasqueradeContext::class);
        $router->aliasMiddleware('masquerade.ability', BlockMasqueradeAbility::class);

        // Register Blade directives
        Blade::if('masquerading', function (): bool {
            return app(MasqueradeManager::class)->isMasquerading();
        });
        Blade::directive('masqueradeBanner', function (): string {
            return "<?php if (config('masquerade.banner.enabled', true)) { echo view(config('masquerade.banner.view', 'masquerade::banner'))->render(); } ?>";
        });

        // Ensure that the package's resources are published when running in the console
        if (! $this->app->runningInConsole()) {
            return;
        }

        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/masquerade.php' => config_path('masquerade.php'),
        ], 'masquerade-config');

        //




        // Get the current timestamp to ensure unique migration filenames
        $timestamp = time();

        // Publish migrations with unique timestamps
        $this->publishes([
            __DIR__.'/../database/migrations/create_masquerade_logs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', $timestamp).'_create_masquerade_logs_table.php'),
            __DIR__.'/../database/migrations/add_v1_1_columns_to_masquerade_logs_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', $timestamp + 1).'_add_v1_1_columns_to_masquerade_logs_table.php'),
            __DIR__.'/../database/migrations/create_masquerade_notes_table.php.stub' => database_path('migrations/'.date('Y_m_d_His', $timestamp + 2).'_create_masquerade_notes_table.php'),
        ], 'masquerade-migrations');

        $this->publishes([
            __DIR__.'/../resources/views/banner.blade.php' => resource_path('views/vendor/masquerade/banner.blade.php'),
        ], 'masquerade-views');

        $this->commands([
            InstallCommand::class,
            PruneMasqueradeLogsCommand::class,
            ExportMasqueradeLogsCommand::class,
        ]);
    }
}
