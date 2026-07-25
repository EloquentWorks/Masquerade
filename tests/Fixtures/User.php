<?php

namespace EloquentWorks\Masquerade\Tests\Fixtures;

use EloquentWorks\Masquerade\Traits\HasMasquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

/**
 * A simple user model for testing purposes.
 */
final class User extends AuthenticatableUser
{
    use HasMasquerade;

    /**
     * The guarded attributes on the model.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'is_owner' => 'boolean',
    ];

    /**
     * Determine if the user can masquerade as the given target.
     */
    public function canMasquerade(Authenticatable $target): bool
    {
        return (bool) $this->getAttribute('is_admin')
            && (! $target instanceof Model || ! $this->is($target));
    }

    /**
     * Determine if the user can be masqueraded by the given impersonator.
     */
    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return ! (bool) $this->getAttribute('is_owner');
    }
}
