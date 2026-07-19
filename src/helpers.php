<?php

use EloquentWorks\Masquerade\MasqueradeManager;

/**
 * Get the MasqueradeManager instance.
 *
 * @return MasqueradeManager
 */
if (! function_exists('masquerade')) {
    function masquerade(): MasqueradeManager
    {
        // Retrieve the MasqueradeManager instance from the service container
        return app(MasqueradeManager::class);
    }
}
