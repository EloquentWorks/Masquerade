<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface CanBeMasqueraded
 *
 * This interface defines a contract for models that can be impersonated (masqueraded) by other users.
 */
interface CanBeMasqueraded
{
    /**
     * Determines if the current user can be impersonated by the given impersonator.
     *
     * @param  Authenticatable  $impersonator  The user attempting to impersonate.
     * @return bool True if the current user can be impersonated, false otherwise.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool;
}
