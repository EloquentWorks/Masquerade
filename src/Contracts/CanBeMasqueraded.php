<?php

namespace EloquentWorks\Masquerade\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Interface for models that can be masqueraded.
 */
interface CanBeMasqueraded
{
    /**
     * Determine if the model can be masqueraded by the given impersonator.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool;
}
