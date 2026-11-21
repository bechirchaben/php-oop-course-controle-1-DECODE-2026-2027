<?php

namespace App\lib\Http;

class Request {
    private readonly string $uri;
    private readonly string $method;
    private readonly array $headers;
    private readonly string $rawBody;
    private array $pathParams = [];

    public function __construct()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $this->uri = is_string($path) ? $path : '/';
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->headers = getallheaders();
        $this->rawBody = (string) file_get_contents('php://input');
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * HTTP header names are case-insensitive.
     */
    public function getHeader(string $name): ?string
    {
        foreach ($this->headers as $headerName => $value) {
            if (strcasecmp($headerName, $name) === 0) {
                return $value;
            }
        }

        return null;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }

    /**
     * True when the Content-Type header is application/json (parameters such as charset are allowed).
     */
    public function hasJsonContentType(): bool
    {
        $contentType = $this->getHeader('Content-Type');

        if ($contentType === null) {
            return false;
        }

        $mediaType = strtolower(trim(explode(';', $contentType)[0]));

        return $mediaType === 'application/json';
    }

    /**
     * True when the client accepts a JSON response (no Accept header means anything is accepted).
     */
    public function acceptsJson(): bool
    {
        $accept = $this->getHeader('Accept');

        if ($accept === null || trim($accept) === '') {
            return true;
        }

        foreach (explode(',', $accept) as $mediaRange) {
            $mediaType = strtolower(trim(explode(';', $mediaRange)[0]));

            if (in_array($mediaType, ['application/json', 'application/*', '*/*'], true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Decodes the body as a JSON object.
     *
     * @throws \JsonException when the body is not valid JSON
     * @throws \UnexpectedValueException when the body is valid JSON but not an object
     */
    public function getJsonBody(): array
    {
        $decoded = json_decode($this->rawBody, false, 512, JSON_THROW_ON_ERROR);

        if (!$decoded instanceof \stdClass) {
            throw new \UnexpectedValueException('the request body must be a JSON object');
        }

        return get_object_vars($decoded);
    }

    public function setPathParams(array $pathParams): void
    {
        $this->pathParams = $pathParams;
    }

    public function getPathParam(string $name): ?string
    {
        return $this->pathParams[$name] ?? null;
    }
}
