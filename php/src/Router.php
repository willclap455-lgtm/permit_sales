<?php

declare(strict_types=1);

namespace PermitSales;

final class Router
{
    /**
     * @var array<int, array{method:string, pattern:string, handler:callable}>
     */
    private array $routes = [];

    public function add(string $method, string $pattern, callable $handler): void
    {
        $this->routes[] = [
            'method'  => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        $path = '/' . ltrim($path, '/');
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }
            $regex = $this->compile($route['pattern']);
            if (preg_match($regex, $path, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                ($route['handler'])($params);
                return;
            }
        }

        http_response_code(404);
        View::render('pages/not_found', ['title' => 'Not found']);
    }

    private function compile(string $pattern): string
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        return '#^' . $regex . '$#';
    }
}
