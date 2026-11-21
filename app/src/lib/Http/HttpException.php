<?php

namespace App\lib\Http;

/**
 * Thrown by a controller to stop processing and answer with an HTTP error.
 */
class HttpException extends \RuntimeException {
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly array $details = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public function toResponse(): Response
    {
        return Response::jsonError($this->getMessage(), $this->statusCode, $this->details);
    }
}
