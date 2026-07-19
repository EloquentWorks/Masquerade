<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface CanMasquerade
 *
 * This interface defines a contract for user models that can impersonate (masquerade) other users.
 * Implementing this interface allows you to control which users can impersonate others.
 */
interface CanMasquerade
{
    /**
     * Determine if the current user can masquerade as the given target user.
     *
     * @param Authenticatable $target The user that the current user wants to impersonate.
     * @return bool True if the current user can masquerade as the target user, false otherwise.
     */
    public function canMasquerade(Authenticatable $target): bool;
}
