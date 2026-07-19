<?php

namespace EloquentWorks\Masquerade\Traits;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Trait HasMasquerade
 *
 * This trait provides default implementations for the CanMasquerade and CanBeMasqueraded interfaces.
 * It can be used in user models to enable masquerading functionality.
 */
trait HasMasquerade
{
    /**
     * Override this in your app to protect sensitive users.
     *
     * @param Authenticatable $target The user that the current user wants to impersonate.
     * @return bool True if the current user can masquerade as the target user, false
     */
    public function canMasquerade(Authenticatable $target): bool
    {
        return false;
    }

    /**
     * Override this in your app to protect sensitive users.
     *
     * @param Authenticatable $impersonator The user that is attempting to impersonate the current user.
     * @return bool True if the current user can be masqueraded by the impersonator, false otherwise.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return true;
    }

    /**
     * Check if the current user is currently masquerading as another user.
     *
     * @return bool True if the current user is masquerading, false otherwise.
     */
    public function isMasquerading(): bool
    {
        return masquerade()->isMasquerading();
    }
}
