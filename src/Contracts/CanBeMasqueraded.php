<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface CanBeMasqueraded
{
    /**
     * Determine if the model can be masqueraded by the given impersonator.
     *
     * @param  Authenticatable  $impersonator  The user attempting to masquerade
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool;
}
