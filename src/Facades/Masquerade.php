<?php

namespace EloquentWorks\Masquerade\Facades;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(Authenticatable $target, ?Authenticatable $impersonator = null, ?string $guard = null, ?string $reason = null, array $metadata = [])
 * @method static void stop(?string $guard = null, bool $expired = false)
 * @method static bool isMasquerading()
 * @method static bool hasExpired()
 * @method static bool stopIfExpired()
 * @method static bool canMasquerade(Authenticatable $impersonator, Authenticatable $target)
 * @method static ?Authenticatable impersonator()
 * @method static ?Authenticatable target()
 * @method static ?string uuid()
 * @method static array context()
 *
 * @see \EloquentWorks\Masquerade\MasqueradeManager
 */
final class Masquerade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'masquerade';
    }
}
