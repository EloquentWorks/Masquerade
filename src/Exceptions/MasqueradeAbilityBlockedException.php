<?php

namespace EloquentWorks\Masquerade\Exceptions;

/**
 * Exception thrown when a specific ability is blocked while masquerading.
 */
final class MasqueradeAbilityBlockedException extends MasqueradeException
{
    /**
     * Create a new instance of the exception for a specific ability.
     */
    public static function forAbility(string $ability): self
    {
        return new self(str_replace(
            ':ability',
            $ability,
            (string) config('masquerade.messages.ability_blocked', 'The [:ability] ability is blocked while masquerading.')
        ));
    }
}
