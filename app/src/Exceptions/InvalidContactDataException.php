<?php

namespace App\Exceptions;

/**
 * Thrown when stored contact data does not have the expected structure.
 */
final class InvalidContactDataException extends \RuntimeException {
    public function __construct(string $message, private readonly array $errors = [])
    {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
