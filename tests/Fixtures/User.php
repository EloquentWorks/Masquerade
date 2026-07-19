<?php

declare(strict_types=1);

namespace EloquentWorks\Masquerade\Tests\Fixtures;

use EloquentWorks\Masquerade\Traits\HasMasquerade;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as AuthenticatableUser;

final class User extends AuthenticatableUser
{
    use HasFactory;
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
        return (bool) $this->getAttribute('is_admin') && ! $this->is($target);
    }

    public function canBeMasqueradedBy(Authenticatable $impersonator): bool
    {
        return ! (bool) $this->getAttribute('is_owner');
    }
}
