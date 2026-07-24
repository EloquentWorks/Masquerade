<?php

namespace EloquentWorks\Masquerade\Exceptions;

/**
 * Exception thrown when a masquerade session has expired.
 */
final class MasqueradeExpiredException extends MasqueradeException
{
    /**
     * Create a new instance of the exception.
     *
     * @return self
     */
    public static function make(): self
    {
        return new self((string) config('masquerade.messages.expired', 'Your masquerade session has expired.'));
    }
}
