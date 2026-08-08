<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CanMasquerade
{
    /**
     * Determine if the current user can masquerade as the given target user.
     *
     * @param  Authenticatable  $target  The user to masquerade as
     */
    public function canMasquerade(Authenticatable $target): bool;
}
