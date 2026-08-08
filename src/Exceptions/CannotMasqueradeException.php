<?php

namespace EloquentWorks\Masquerade\Exceptions;

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
