<?php

use EloquentWorks\Masquerade\Models\MasqueradeLog;

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Guard
    |--------------------------------------------------------------------------
    |
    | Null means Masquerade will use Laravel's default guard. You may set this
    | to "web" or another session guard if your application needs it.
    |
    */

    'guard' => null,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | Used by the optional built-in controller routes. If your app uses a custom
    | user model, publish this config file and change this value.
    |
    */

    'user_model' => env('AUTH_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Session Keys
    |--------------------------------------------------------------------------
    |
    | The session key used to store the original user ID when masquerading.
    |
    */

    'session_key' => 'masquerade',

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Disable these if you want to build your own controller/actions and only
    | use the manager, middleware, events, and logs.
    |
    */

    'routes' => [
        'enabled' => true,
        'prefix' => 'masquerade',
        'middleware' => ['web', 'auth'],
        'name' => 'masquerade.',
        'start_route_parameter' => 'user',
        'redirect_after_start' => '/',
        'redirect_after_stop' => '/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Checks
    |--------------------------------------------------------------------------
    |
    | If enabled, the package checks methods on your user models:
    | - $impersonator->canMasquerade($target)
    | - $target->canBeMasqueradedBy($impersonator)
    |
    */

    'permissions' => [
        'use_model_methods' => true,
        'impersonator_method' => 'canMasquerade',
        'target_method' => 'canBeMasqueradedBy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | List of security features to prevent abuse of masquerading. You may disable any of these if your application needs it.
    |
    */

    'security' => [
        'allow_nested' => false,
        'allow_same_user' => false,
        'require_reason' => false,
        'regenerate_session_id' => true,
        'logout_on_missing_original_user' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Duration Limit
    |--------------------------------------------------------------------------
    |
    | When enabled, the duration middleware will automatically stop an expired
    | masquerade session.
    |
    */

    'duration' => [
        'enabled' => true,
        'minutes' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for audit logging of masquerade actions.
    |
    */

    'logging' => [
        'enabled' => true,
        'model' => MasqueradeLog::class,
        'table_name' => 'masquerade_logs',
        'store_ip_address' => true,
        'store_user_agent' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Banner
    |--------------------------------------------------------------------------
    |
    | Configuration for the optional banner that appears when masquerading.
    |
    */

    'banner' => [
        'enabled' => true,
        'view' => 'masquerade::banner',
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    |
    | Custom messages for masquerade actions. You may customize these to fit your application.
    |
    */

    'messages' => [
        'started' => 'You are now masquerading as another user.',
        'stopped' => 'You have returned to your original account.',
        'denied' => 'You are not allowed to masquerade as this user.',
        'expired' => 'Your masquerade session has expired.',
        'blocked' => 'This action is blocked while masquerading.',
    ],

];
