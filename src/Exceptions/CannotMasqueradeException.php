<?php

namespace EloquentWorks\Masquerade\Exceptions;

/**
 * Exception thrown when a user is not allowed to masquerade as another user.
 */
final class CannotMasqueradeException extends MasqueradeException
{
    /**
     * Create a new exception instance with a specific reason.
     *
     * @param string $reason The reason for the exception.
     */
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
