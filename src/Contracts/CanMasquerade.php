<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface for models that can masquerade as other users.
 */
interface CanMasquerade
{
    /**
     * Determine if the current user can masquerade as the given target user.
     */
    public function canMasquerade(Authenticatable $target): bool;
}
