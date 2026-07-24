<?php

namespace EloquentWorks\Masquerade\Enums;

/**
 * Enum representing the different actions that can occur during a masquerade session.
 */
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
