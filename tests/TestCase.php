<?php

namespace EloquentWorks\Masquerade\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\MasqueradeServiceProvider;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    /**
     * @param  mixed  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            MasqueradeServiceProvider::class,
        ];
    }

    /**
     * @param  mixed  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);

        $app['config']->set('masquerade.user_model', User::class);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
        });

        $migration = include __DIR__.'/../database/migrations/2026_07_18_000009_create_masquerade_logs_table.php';
        $migration->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createUser(array $attributes = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'User',
            'email' => uniqid('user_', true).'@example.com',
            'is_admin' => false,
            'is_owner' => false,
        ], $attributes));
    }
}
