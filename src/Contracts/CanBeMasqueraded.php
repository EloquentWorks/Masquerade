<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface CanBeMasqueraded
 *
 * This interface defines a contract for user models that can be impersonated (masqueraded) by other users.
 * Implementing this interface allows you to control which users can impersonate others.
 */
interface CanBeMasqueraded
{
    /**
     * Determine if the current user can be masqueraded by the given impersonator.
     *
     * @param Authenticatable $impersonator The user attempting to impersonate this user.
     * @return bool True if the impersonator can masquerade as this user, false otherwise.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool;
}
