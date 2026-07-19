<?php

use EloquentWorks\Masquerade\Http\Controllers\MasqueradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Masquerade Routes
|--------------------------------------------------------------------------
|
| These routes are only loaded if the "routes.enabled" config value is true.
| You may disable these and build your own controller/actions if you prefer.
|
*/

Route::group([
    'prefix' => config('masquerade.routes.prefix', 'masquerade'),
    'as' => config('masquerade.routes.name', 'masquerade.'),
    'middleware' => config('masquerade.routes.middleware', ['web', 'auth']),
], function (): void {
    Route::post('/{user}/start', [MasqueradeController::class, 'start'])
        ->name('start');

    Route::post('/stop', [MasqueradeController::class, 'stop'])
        ->name('stop');
});
