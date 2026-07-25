<?php

namespace EloquentWorks\Masquerade\Exceptions;

/**
 * Exception thrown when a masquerade action cannot be performed.
 */
final class CannotMasqueradeException extends MasqueradeException
{
    /**
     * Create a new instance of the exception with a specific reason.
     */
    public static function because(string $reason): self
    {
        return new self($reason);
    }
}
