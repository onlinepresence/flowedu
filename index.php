<?php
// Include the routes and middleware
$routes = require 'routes.php';
require_once "includes/load_env.php";
require "vendor/autoload.php";

// Parse the request URI and normalize it
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/') ?: '/';

require 'middleware.php';


if($requestUri == "/shutdown"){
    require "shutdown.php"; exit;
}

// build the dynamic route files
require_once "include/routes.php";

/**
 * Match the current request URI against defined routes.
 *
 * @param string $requestUri
 * @param array $routes
 * @return array [config, params] or [null, []] if no match is found.
 */
function matchRoute($requestUri, $routes) {
    foreach ($routes as $route => $config) {
        // Handle prefixed groups
        if (isset($config['prefix'])) {
            if (str_starts_with($requestUri, $config['prefix'])) {

                $subRoute = str_replace($config['prefix'], '', $requestUri) ?: '/';

                foreach ($config['routes'] as $subPath => $subConfig) {

                    // Extract param names
                    preg_match_all('/\{(\w+)\}/', $subPath, $paramNames);

                    // Build regex pattern
                    $pattern = "#^" . preg_replace('/\{\w+\}/', '([^/]+)', $subPath) . "$#";

                    if (preg_match($pattern, $subRoute, $matches)) {

                        array_shift($matches);

                        // Build associative params
                        $params = [];
                        foreach ($paramNames[1] as $i => $name) {
                            $params[$name] = $matches[$i] ?? null;
                        }

                        $subConfig['middleware'] = array_merge($config['middleware'] ?? [], $subConfig['middleware'] ?? []);

                        return [$subConfig, $params];
                    }
                }
            }
        }

        // Handle normal routes
        else {

            preg_match_all('/\{(\w+)\}/', $route, $paramNames);

            $pattern = "#^" . preg_replace('/\{\w+\}/', '([^/]+)', $route) . "$#";

            if (preg_match($pattern, $requestUri, $matches)) {

                array_shift($matches);

                $params = [];
                foreach ($paramNames[1] as $i => $name) {
                    $params[$name] = $matches[$i] ?? null;
                }

                return [$config, $params];
            }
        }
    }

    return [null, []];
}

// Match the current route
list($config, $params) = matchRoute($requestUri, $routes);

if ($config) {
    $file = $config['file'];
    $middlewares = $config['middleware'] ?? [];

    /**
     * Middleware execution stack
     * Middlewares are executed in reverse order, and the request ultimately reaches the route file.
     */
    $middlewareStack = function () use ($file, $params) {
        // Pass dynamic parameters to the route file
        extract($params);
        require $file;
    };

    foreach (array_reverse($middlewares) as $middleware) {
        $middlewareStack = function () use ($middleware, $middlewareStack) {
            if (is_callable($middleware)) {
                $middleware($middlewareStack); // Execute middleware
            } elseif (function_exists($middleware)) {
                $middleware($middlewareStack); // Named middleware
            } else {
                throw new Exception("Middleware $middleware is not callable.");
            }
        };
    }

    // Run the middleware stack
    $middlewareStack();
} else {
    http_response_code(404);
    require '404.php';
}
