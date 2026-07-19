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
     * Determines if the current user can impersonate the given target user.
     *
     * @param  Authenticatable  $target  The user to be impersonated.
     * @return bool True if the current user can impersonate the target, false otherwise.
     */
    public function canMasquerade(Authenticatable $target): bool;
}
