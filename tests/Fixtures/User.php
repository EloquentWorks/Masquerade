<?php

namespace EloquentWorks\Masquerade\Tests\Fixtures;

use EloquentWorks\Masquerade\Traits\HasMasquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

final class User extends AuthenticatableUser
{
    use HasMasquerade;

    /**
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_admin' => 'boolean',
        'is_owner' => 'boolean',
    ];

    public function canMasquerade(Authenticatable $target): bool
    {
        return (bool) $this->getAttribute('is_admin')
            && (! $target instanceof Model || ! $this->is($target));
    }

    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return ! (bool) $this->getAttribute('is_owner');
    }
}
