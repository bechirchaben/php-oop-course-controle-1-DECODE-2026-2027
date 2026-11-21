<?php


namespace App\lib\Http;

use App\lib\Controllers\AbstractController;

class Router {

    private const string ROUTE_CONFIG_PATH = __DIR__ . '/../../../config/routes.json';
    private const string CONTROLLER_NAMESPACE = 'App\\Controllers\\';

    public function main(Request $request): Response {
        $routes = $this->getRoutes();
        $allowedMethods = [];

        foreach($routes as $route) {
            $this->validateConfig($route);

            $pathParams = $this->matchUri($request, $route['uri']);

            if($pathParams === null) {
                continue;
            }
            $allowedMethods[] = strtoupper($route['method']);

            if($this->checkMethod($request, $route['method']) === false) {
                continue;
            }

            try {
                $controllerInstance = $this->getValidControllerInstance($route['controller']);
            } catch(\Exception $e) {
                error_log($e->getMessage());

                return Response::jsonError('internal server error', 500);
            }

            $request->setPathParams($pathParams);

            try {
                return $controllerInstance->process($request);
            } catch(\Throwable $e) {
                error_log((string) $e);

                return Response::jsonError('internal server error', 500);
            }
        }

        if($allowedMethods !== []) {
            $response = Response::jsonError('invalid method', 405);
            $response->headers['Allow'] = implode(', ', array_unique($allowedMethods));

            return $response;
        }

        return Response::jsonError('route not found', 404);
    }

    private function getRoutes(): array {
        if(file_exists(self::ROUTE_CONFIG_PATH) === false) {
            echo "Le fichier de config des routes n'a pas été trouvé";
            exit();
        }

        $routesConfigContent = file_get_contents(self::ROUTE_CONFIG_PATH);

        return json_decode($routesConfigContent, true, 512, JSON_THROW_ON_ERROR);
    }

    private function validateConfig(array $route): void {
        if(array_key_exists('uri', $route) === false
            || array_key_exists('method', $route) === false
            || array_key_exists('controller', $route) === false) {
            throw new \Exception('Le fichier de config est mal écrit', 500);
        }
    }

    /**
     * Matches the request uri against a route uri pattern.
     * A pattern segment written as {name} matches any non-empty segment and is captured as a path parameter.
     *
     * @return array<string, string>|null the captured path parameters, or null if the uri does not match
     */
    private function matchUri(Request $request, string $uriPattern): ?array {
        $regex = preg_replace_callback(
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)\}|[^{]+#',
            static function (array $matches): string {
                if(isset($matches[1]) && $matches[1] !== '') {
                    return '(?P<' . $matches[1] . '>[^/]+)';
                }

                return preg_quote($matches[0], '#');
            },
            $uriPattern
        );

        if(preg_match('#^' . $regex . '$#', $request->getUri(), $matches) !== 1) {
            return null;
        }

        $pathParams = [];

        foreach($matches as $name => $value) {
            if(is_string($name)) {
                $pathParams[$name] = rawurldecode($value);
            }
        }

        return $pathParams;
    }

    private function checkMethod(Request $request, string $method): bool {
        return $request->getMethod() === strtoupper($method);
    }

    private function getValidControllerInstance(string $routeController): AbstractController {

        $controllerName = self::CONTROLLER_NAMESPACE . $routeController;

        if(class_exists($controllerName) === false) {
            throw new \Exception('Controller ' . $controllerName . ' not found.');
        }

        $controllerInstance = new $controllerName();

        if(is_subclass_of($controllerInstance, AbstractController::class) === false) {
            throw new \Exception('Controller ' . $controllerName . ' must extends App\lib\Controllers\AbstractController.');
        }

        return $controllerInstance;
    }

}
