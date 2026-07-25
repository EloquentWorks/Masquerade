<?php

namespace EloquentWorks\Masquerade\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\MasqueradeServiceProvider;
use EloquentWorks\Masquerade\Tests\Fixtures\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Base test case for the Masquerade package.
 */
abstract class TestCase extends OrchestraTestCase
{
    /**
     * Get the package providers for the test application.
     *
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
     * Define the environment setup for the test application.
     *
     * @param  mixed  $app
     */
    protected function defineEnvironment($app): void
    {
        // Set up the application key and database configuration for testing
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('x', 32)));

        // Use an in-memory SQLite database for testing
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Set up authentication configuration for testing
        $app['config']->set('auth.defaults.guard', 'web');
        $app['config']->set('auth.guards.web', [
            'driver' => 'session',
            'provider' => 'users',
        ]);

        // Set up the user provider to use the User model for testing
        $app['config']->set('auth.providers.users', [
            'driver' => 'eloquent',
            'model' => User::class,
        ]);

        // Set the masquerade user model to the User class for testing
        $app['config']->set('masquerade.user_model', User::class);
    }

    /**
     * Define database migrations for the test environment.
     */
    protected function defineDatabaseMigrations(): void
    {
        // Create the users table for testing
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->boolean('is_admin')->default(false);
            $table->boolean('is_owner')->default(false);
            $table->timestamps();
        });

        // Include and run the masquerade logs migration
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    /**
     * Set up the test environment.
     */
    protected function tearDown(): void
    {
        // Reset any mocked time after each test to avoid side effects
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /**
     * Create a new user instance for testing.
     *
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
