<?php

namespace App\lib\Controllers;

use App\lib\Http\HttpException;
use App\lib\Http\Request;
use App\lib\Http\Response;

/**
 * Base controller for JSON API endpoints.
 * It checks the request headers before handing over to the concrete controller,
 * and turns any HttpException thrown by the controller into a JSON error response.
 */
abstract class AbstractJsonController extends AbstractController {

    final public function process(Request $request): Response
    {
        try {
            if ($request->acceptsJson() === false) {
                throw new HttpException(406, 'this API only produces application/json responses');
            }

            if ($this->expectsJsonBody() && $request->hasJsonContentType() === false) {
                throw new HttpException(415, 'the request Content-Type header must be application/json');
            }

            return $this->handle($request);
        } catch (HttpException $e) {
            return $e->toResponse();
        }
    }

    /**
     * Override to return true for endpoints that receive a JSON body (POST, PUT, PATCH).
     */
    protected function expectsJsonBody(): bool
    {
        return false;
    }

    abstract protected function handle(Request $request): Response;

    /**
     * @throws HttpException 400 when the body is empty, not valid JSON or not a JSON object
     */
    protected function decodeJsonBody(Request $request): array
    {
        if (trim($request->getRawBody()) === '') {
            throw new HttpException(400, 'the request body is empty');
        }

        try {
            return $request->getJsonBody();
        } catch (\JsonException $e) {
            throw new HttpException(400, 'the request body is not valid JSON', ['json' => $e->getMessage()], $e);
        } catch (\UnexpectedValueException $e) {
            throw new HttpException(400, $e->getMessage(), [], $e);
        }
    }
}
