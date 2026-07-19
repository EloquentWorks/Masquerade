<?php

namespace EloquentWorks\Masquerade\Enums;

/**
 * @method static self Started()
 * @method static self Ended()
 * @method static self Denied()
 * @method static self Expired()
 * @method static self Extended()
 */
enum MasqueradeAction: string
{
    case Started = 'started';
    case Ended = 'ended';
    case Denied = 'denied';
    case Expired = 'expired';
    case Extended = 'extended';
}
