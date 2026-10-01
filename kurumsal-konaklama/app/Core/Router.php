<?php
declare(strict_types=1);

namespace App\Core;

use App\Exceptions\HttpException;

final class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:array|callable, middleware:array, name:?string}> */
    private array $routes = [];
    private array $named = [];
    private array $groupStack = [];

    public function get(string $pattern, array|callable $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler, $middleware, $name);
    }

    public function post(string $pattern, array|callable $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add(['POST'], $pattern, $handler, $middleware, $name);
    }

    public function match(array $methods, string $pattern, array|callable $handler, array $middleware = [], ?string $name = null): void
    {
        $this->add($methods, $pattern, $handler, $middleware, $name);
    }

    public function group(string $prefix, array $middleware, callable $callback): void
    {
        $this->groupStack[] = ['prefix' => $prefix, 'middleware' => $middleware];
        $callback($this);
        array_pop($this->groupStack);
    }

    private function add(array $methods, string $pattern, array|callable $handler, array $middleware, ?string $name): void
    {
        $prefix = '';
        $groupMw = [];
        foreach ($this->groupStack as $g) {
            $prefix .= $g['prefix'];
            $groupMw = array_merge($groupMw, $g['middleware']);
        }
        $full = '/' . trim($prefix . '/' . trim($pattern, '/'), '/');
        $regex = preg_replace_callback(
            '#\{(\w+)(?::([^}]+))?\}#',
            static fn ($m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $full,
        );
        foreach ($methods as $method) {
            $this->routes[] = [
                'method' => $method,
                'pattern' => $full,
                'regex' => '#^' . $regex . '$#u',
                'handler' => $handler,
                'middleware' => array_merge($groupMw, $middleware),
                'name' => $name,
            ];
        }
        if ($name !== null) {
            $this->named[$name] = $full;
        }
    }

    /** İsimli rotadan yol üretir: route('hotel.show', ['slug' => 'x']). */
    public function url(string $name, array $params = [], array $query = []): string
    {
        $pattern = $this->named[$name] ?? null;
        if ($pattern === null) {
            throw new \InvalidArgumentException('Tanımsız rota: ' . $name);
        }
        $path = preg_replace_callback('#\{(\w+)(?::[^}]+)?\}#', static function ($m) use ($params) {
            if (!array_key_exists($m[1], $params)) {
                throw new \InvalidArgumentException('Eksik rota parametresi: ' . $m[1]);
            }
            return rawurlencode((string) $params[$m[1]]);
        }, $pattern);
        return Url::to((string) $path, $query);
    }

    /** @return array{0: array, 1: array<string,string>} */
    public function resolve(string $method, string $path): array
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if ($route['method'] !== $method) {
                $allowed = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return [$route, $params];
        }
        throw new HttpException($allowed ? 405 : 404);
    }

    public function routes(): array
    {
        return $this->routes;
    }
}
