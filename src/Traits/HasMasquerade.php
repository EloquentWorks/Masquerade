<?php

namespace EloquentWorks\Masquerade\Traits;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Trait HasMasquerade
 */
trait HasMasquerade
{
    /**
     * Override this in your app to decide who can start masquerading.
     *
     * @param  Authenticatable  $target
     * @return bool
     */
    public function canMasquerade(Authenticatable $target): bool
    {
        return false;
    }

    /**
     * Override this in your app to protect sensitive users.
     *
     * @param  Authenticatable  $impersonator
     * @return bool
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return true;
    }

    /**
     * Check if the current user is masquerading.
     *
     * @return bool
     */
    public function isMasquerading(): bool
    {
        return masquerade()->isMasquerading();
    }
}
