<?php
namespace App;

class RouteDefinition
{
    public string $method;
    public string $path;
    public string $handler;
    public array  $middlewares = [];

    public function __construct(string $method, string $path, string $handler)
    {
        $this->method  = $method;
        $this->path    = $path;
        $this->handler = $handler;
    }

    public function middleware(string $middlewares): static
    {
        $this->middlewares = array_map('trim', explode(',', $middlewares));
        return $this;
    }
}

class Route
{
    private static array $routes = [];

    public static function get(string $path, string $handler): RouteDefinition
    {
        return self::add('GET', $path, $handler);
    }

    public static function post(string $path, string $handler): RouteDefinition
    {
        return self::add('POST', $path, $handler);
    }

    private static function add(string $method, string $path, string $handler): RouteDefinition
    {
        $route           = new RouteDefinition($method, $path, $handler);
        self::$routes[]  = $route;
        return $route;
    }

    public static function dispatch(string $method, string $uri): void
    {
        $uri = strtok($uri, '?') ?: '/';

        foreach (self::$routes as $route) {
            if ($route->method !== strtoupper($method)) {
                continue;
            }

            // Convert {param} placeholders to named regex groups
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route->path);
            $pattern = '#^' . $pattern . '$#';

            if (!preg_match($pattern, $uri, $matches)) {
                continue;
            }

            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            foreach ($route->middlewares as $mw) {
                self::runMiddleware($mw);
            }

            [$controllerClass, $action] = explode('@', $route->handler);
            $fqcn       = 'App\\Controllers\\' . $controllerClass;
            $controller = new $fqcn();
            $controller->$action(...array_values($params));
            return;
        }

        http_response_code(404);
        include VIEWS_PATH . '/errors/404.php';
    }

    private static function runMiddleware(string $name): void
    {
        $map = [
            'auth'       => \App\Middleware\AuthMiddleware::class,
            'csrf'       => \App\Middleware\CsrfMiddleware::class,
            'rate_limit' => \App\Middleware\RateLimitMiddleware::class,
        ];
        if (isset($map[$name])) {
            (new $map[$name]())->handle();
        }
    }
}
