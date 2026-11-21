<?php

namespace App\lib\Http;

class Response {
    public int $statusCode = 200;
    public string $content = '';
    public array $headers = [];

    public static function json(mixed $data, int $statusCode = 200): self
    {
        $response = new self();
        $response->statusCode = $statusCode;
        $response->headers['Content-Type'] = 'application/json; charset=utf-8';
        $response->content = json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION
        );

        return $response;
    }

    public static function jsonError(string $message, int $statusCode, array $details = []): self
    {
        $body = ['error' => $message];

        if ($details !== []) {
            $body['details'] = $details;
        }

        return self::json($body, $statusCode);
    }

    public static function noContent(): self
    {
        $response = new self();
        $response->statusCode = 204;

        return $response;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        if ($this->statusCode !== 204 && $this->statusCode !== 304) {
            echo $this->content;
        }
    }
}
