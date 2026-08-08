<?php

namespace EloquentWorks\Masquerade\Enums;

enum MasqueradeAction: string
{
    case Started = 'started';
    case Ended = 'ended';
    case Denied = 'denied';
    case Expired = 'expired';
    case Extended = 'extended';
    case MetadataUpdated = 'metadata_updated';
    case NoteAdded = 'note_added';
    case AbilityBlocked = 'ability_blocked';
    case RiskDetected = 'risk_detected';
}
