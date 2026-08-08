<?php

namespace EloquentWorks\Masquerade\Facades;

use Carbon\CarbonImmutable;
use EloquentWorks\Masquerade\Data\MasqueradeSession;
use EloquentWorks\Masquerade\MasqueradeManager;
use EloquentWorks\Masquerade\Models\MasqueradeNote;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void start(Authenticatable $target, ?Authenticatable $impersonator = null, ?string $guard = null, ?string $reason = null, array<string, mixed> $metadata = [], ?string $category = null)
 * @method static void stop(?string $guard = null, bool $expired = false, ?string $endedReason = null)
 * @method static CarbonImmutable extend(int $minutes, ?string $reason = null)
 * @method static void updateMetadata(array<string, mixed> $metadata, bool $merge = true)
 * @method static MasqueradeNote addNote(string $note, ?Authenticatable $author = null, array<string, mixed> $metadata = [])
 * @method static Collection<int, MasqueradeNote> notes(?string $uuid = null)
 * @method static bool blocksAbility(string $ability)
 * @method static void assertAbilityAllowed(string $ability)
 * @method static void recordBlockedAbility(string $ability, ?string $reason = null, array<string, mixed> $metadata = [])
 * @method static bool isMasquerading()
 * @method static bool hasExpired()
 * @method static bool stopIfExpired()
 * @method static bool canMasquerade(Authenticatable $impersonator, Authenticatable $target)
 * @method static bool isMasqueradingAs(Authenticatable $target)
 * @method static bool isMasqueradedBy(Authenticatable $impersonator)
 * @method static ?Authenticatable impersonator()
 * @method static ?Authenticatable target()
 * @method static ?string uuid()
 * @method static ?string guard()
 * @method static ?string reason()
 * @method static ?string category()
 * @method static int extensionCount()
 * @method static ?string ticketId()
 * @method static ?string ticketUrl()
 * @method static mixed contextValue(string $key, mixed $default = null)
 * @method static array<string, mixed> metadata()
 * @method static ?CarbonImmutable startedAt()
 * @method static ?CarbonImmutable expiresAt()
 * @method static ?int elapsedSeconds()
 * @method static ?int remainingSeconds()
 * @method static ?MasqueradeSession session()
 * @method static array<string, mixed> context()
 * @method static void clear()
 *
 * @see MasqueradeManager
 */
final class Masquerade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        // Get the Facade accessor for the MasqueradeManager service.
        return 'masquerade';
    }
}
