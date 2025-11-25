<?php

namespace App\Routing;

use App\Application;
use App\Exceptions\HttpException;
use App\Support\Auth;
use App\Support\RequestContext;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use function FastRoute\simpleDispatcher;

class Router
{
    private Application $app;

    /** @var array<int, array{methods: array<int, string>, path: string, handler: callable|array}> */
    private array $routes = [];

    private ?Dispatcher $dispatcher = null;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function get(string $path, callable|array $handler): void
    {
        $this->match(['GET'], $path, $handler);
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function post(string $path, callable|array $handler): void
    {
        $this->match(['POST'], $path, $handler);
    }

    public function patch(string $path, callable|array $handler): void
    {
        $this->match(['PATCH'], $path, $handler);
    }

    public function put(string $path, callable|array $handler): void
    {
        $this->match(['PUT'], $path, $handler);
    }

    public function delete(string $path, callable|array $handler): void
    {
        $this->match(['DELETE'], $path, $handler);
    }

    public function options(string $path, callable|array $handler): void
    {
        $this->match(['OPTIONS'], $path, $handler);
    }

    /**
     * @param array<int, string> $methods
     * @param callable|array{0: class-string, 1: string} $handler
     */
    public function match(array $methods, string $path, callable|array $handler): void
    {
        $this->routes[] = [
            'methods' => array_map('strtoupper', $methods),
            'path' => $path,
            'handler' => $handler,
        ];
        $this->dispatcher = null;
    }

    public function dispatch(Request $request): Response
    {
        if ($this->dispatcher === null) {
            $this->buildDispatcher();
        }

        RequestContext::reset();
        $user = Auth::user($this->app, $request, false);
        RequestContext::setUser($user);

        try {
            $httpMethod = $request->getMethod();
            $uri = rawurldecode($request->getPathInfo());
            error_log("Dispatching: $httpMethod $uri");
            $routeInfo = $this->dispatcher->dispatch($httpMethod, $uri);
            error_log("Route info: " . json_encode($routeInfo));

            return match ($routeInfo[0]) {
                Dispatcher::NOT_FOUND => throw new HttpException('Not Found', 404),
                Dispatcher::METHOD_NOT_ALLOWED => throw new HttpException('Method Not Allowed', 405),
                Dispatcher::FOUND => $this->invokeHandler($routeInfo[1], $routeInfo[2], $request),
                default => throw new HttpException('Not Found', 404),
            };
        } finally {
            RequestContext::reset();
        }
    }

    /**
     * @param callable|array{0: class-string, 1: string} $handler
     * @param array<string, string> $vars
     */
    private function invokeHandler(callable|array $handler, array $vars, Request $request): Response
    {
        if (is_array($handler) && is_string($handler[0])) {
            $class = $handler[0];
            $instance = new $class($this->app);
            $method = $handler[1];
            return $instance->$method($request, $vars);
        }

        return $handler($request, $vars, $this->app);
    }

    private function buildDispatcher(): void
    {
        $routes = $this->routes;
        $this->dispatcher = simpleDispatcher(static function (RouteCollector $collector) use ($routes): void {
            foreach ($routes as $route) {
                $collector->addRoute($route['methods'], $route['path'], $route['handler']);
            }
        });
    }
}
