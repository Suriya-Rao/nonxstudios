<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Small regex router. Patterns use `{name}` placeholders which are passed to
 * the controller as an associative array of parameters.
 */
final class Router
{
    /** @var array<int,array{method:string,regex:string,params:array<int,string>,handler:array}> */
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    public function post(string $path, array $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    private function add(string $method, string $path, array $handler): void
    {
        $params = [];
        $regex  = preg_replace_callback(
            // `{name}` matches one segment; `{name...}` matches the remainder (incl. slashes).
            '#\{([a-zA-Z_][a-zA-Z0-9_]*)(\.\.\.)?\}#',
            function (array $m) use (&$params): string {
                $params[] = $m[1];
                return isset($m[2]) ? '(.*)' : '([^/]+)';
            },
            $path
        );
        $regex = '#^' . rtrim($regex, '/') . '/?$#';

        $this->routes[] = [
            'method'  => $method,
            'regex'   => $regex,
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = Request::method();
        $path   = Request::path();
        $path   = $path === '/' ? '/' : $path;

        foreach ($this->routes as $route) {
            // Match path against every method so we can distinguish 404 / 405.
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }

            if ($route['method'] !== $method) {
                continue;
            }

            array_shift($matches);
            $args = [];
            foreach ($route['params'] as $i => $name) {
                $args[$name] = $matches[$i] ?? null;
            }

            [$class, $action] = $route['handler'];
            $controller = new $class();
            $controller->$action($args);
            return;
        }

        // Distinguish "path exists but wrong verb" from a true 404.
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path)) {
                Response::abort(405, 'That method is not allowed for this address.');
            }
        }

        Response::abort(404, 'The page you are looking for does not exist.');
    }
}
