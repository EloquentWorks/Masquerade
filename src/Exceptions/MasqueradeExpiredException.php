<?php

namespace EloquentWorks\Masquerade\Exceptions;

final class MasqueradeExpiredException extends MasqueradeException
{
    /**
     * Create a new instance of the exception.
     */
    public static function make(): self
    {
        return new self((string) config('masquerade.messages.expired', 'Your masquerade session has expired.'));
    }
}
