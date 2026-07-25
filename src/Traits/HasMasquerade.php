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
     */
    public function canMasquerade(Authenticatable $target): bool
    {
        return false;
    }

    /**
     * Override this in your app to protect sensitive users.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return true;
    }

    /**
     * Check if the current user is masquerading.
     */
    public function isMasquerading(): bool
    {
        return masquerade()->isMasquerading();
    }
}
