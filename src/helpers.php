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
        return app(MasqueradeManager::class);
    }
}
