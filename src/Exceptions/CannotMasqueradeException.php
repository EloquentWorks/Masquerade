<?php

namespace EloquentWorks\Masquerade\Exceptions;

/**
 * Exception thrown when a user cannot masquerade as another user.
 */
final class CannotMasqueradeException extends MasqueradeException
{
    /**
     * Create a new exception instance with a reason.
     */
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
