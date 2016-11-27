<?php

namespace Nerd\Framework\Routing;

use Nerd\Framework\Http\Request\RequestContract;
use Nerd\Framework\Routing\Route\Matcher\MatcherBuilder;
use Nerd\Framework\Routing\Route\Matcher\Matcher;
use Nerd\Framework\Routing\Route\Route;
use Nerd\Framework\Routing\Route\RouteContract;

class Router implements RouterContract
{
    /**
     * List of supported HTTP request methods.
     *
     * @var array
     */
    private static $availableMethods = ["HEAD", "GET", "POST", "PUT", "DELETE"];

    /**
     * List of registered routes.
     *
     * @var RouteContract[][]
     */
    private $routes = [];

    /**
     * List of registered middleware.
     *
     * @var array
     */
    private $middleware = [];

    /**
     * Route handler which will be invoked when router found route matching request.
     *
     * @var callable $globalRouteHandler
     */
    private static $globalRouteHandler;

    /**
     * Middleware handler which will be invoked for all registered middleware.
     *
     * @var callable $globalMiddlewareHandler
     */
    private static $globalMiddlewareHandler;

    /**
     * @var MatcherBuilder
     */
    private $matcherBuilder;


    public function __construct()
    {
        $this->matcherBuilder = new MatcherBuilder();
    }

    /**
     * Set global route handler.
     *
     * @param callable $globalRouteHandler
     */
    public static function setGlobalRouteHandler(callable $globalRouteHandler = null)
    {
        static::$globalRouteHandler = $globalRouteHandler;
    }

    /**
     * Set global middleware handler.
     *
     * @param callable $globalMiddlewareHandler
     */
    public static function setGlobalMiddlewareHandler(callable $globalMiddlewareHandler = null)
    {
        static::$globalMiddlewareHandler = $globalMiddlewareHandler;
    }

    /**
     * Add middleware to router.
     *
     * @param string $pattern
     * @param callable $middleware
     * @return Router
     */
    public function middleware(string $pattern, callable $middleware)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        $this->middleware[] = [$matcher, $middleware];

        return $this;
    }

    /**
     * Add route into routes list.
     *
     * @param string|array $methods
     * @param string $pattern
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function add(array $methods, string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);
        $route = new Route($matcher, $action, $data);

        foreach ($methods as $method) {
            if (!array_key_exists($method, $this->routes)) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = $route;
        }

        return $this;
    }

    /**
     * Add route for GET method into routes list.
     *
     * @param string $pattern
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function get(string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["HEAD", "GET"], $matcher, $action, $data);
    }

    /**
     * Add route for POST method into routes list.
     *
     * @param string $pattern
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function post(string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["POST"], $matcher, $action, $data);
    }

    /**
     * Add route for PUT method into routes list.
     *
     * @param string $pattern
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function put(string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["PUT"], $matcher, $action, $data);
    }

    /**
     * Add route for DELETE method into routes list.
     *
     * @param string $pattern
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function delete(string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["DELETE"], $matcher, $action, $data);
    }

    /**
     * @param string $pattern
     * @param callable $action
     * @param null $data
     * @return Router
     */
    public function any(string $pattern, callable $action, $data = null)
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(self::$availableMethods, $matcher, $action, $data);
    }

    /**
     * Find action matching HTTP request and invoke it.
     *
     * @param RequestContract $request
     * @return mixed
     * @throws RouterException
     */
    public function handle(RequestContract $request)
    {
        $route = $this->findMatchingRoute($request);

        if (is_null($route)) {
            throw new RouteNotFoundException("Document {$request->getPath()} not found on this server.");
        }

        $middleware = $this->findMiddleware($request);

        return $this->cascadeMiddlewareWithRoute($middleware, $route, $request);
    }

    /**
     * Cascades middleware list with route action.
     *
     * @param array $middleware
     * @param RouteContract $route
     * @param RequestContract $request
     * @return mixed
     */
    private function cascadeMiddlewareWithRoute(array $middleware, RouteContract $route, RequestContract $request)
    {
        $invokeRoute = function () use ($route, $request) {
            return $this->invokeRoute($route, $request);
        };

        $action = array_reduce(array_reverse($middleware), function ($first, $second) {
            return function () use ($first, $second) {
                list($middleware, $args) = $second;
                return $this->invokeMiddleware($middleware, $first, $args);
            };
        }, $invokeRoute);

        return call_user_func($action);
    }

    /**
     * Gets route matching request.
     *
     * @param RequestContract $request
     * @return RouteContract|null
     */
    private function findMatchingRoute(RequestContract $request)
    {
        $method = $request->getMethod();

        if (!array_key_exists($method, $this->routes)) {
            return null;
        }

        foreach ($this->routes[$method] as $route) {
            if ($route->matches($request)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * Find middleware matching given path.
     *
     * @param RequestContract $request
     * @return array
     */
    private function findMiddleware(RequestContract $request)
    {
        $path = $request->getPath();

        $middleware = [];

        /**
         * @var Matcher $matcher
         */
        foreach ($this->middleware as list($matcher, $action)) {
            if ($matcher->matches($path)) {
                $middleware[] = [$action, $matcher->extractParameters($path)];
            }
        }

        return $middleware;
    }

    /**
     * @param RouteContract $route
     * @param RequestContract $request
     * @return mixed
     */
    private function invokeRoute(RouteContract $route, RequestContract $request)
    {
        if (is_callable(self::$globalRouteHandler)) {
            return call_user_func(self::$globalRouteHandler, $request, $route);
        }

        $parameters = $route->parameters($request);

        return call_user_func_array($route->getAction(), array_values($parameters));
    }

    /**
     * @param callable $action
     * @param callable $next
     * @param array $args
     * @return mixed
     * @throws RouterException
     */
    private function invokeMiddleware($action, $next, array $args)
    {
        if (is_callable(self::$globalMiddlewareHandler)) {
            return call_user_func(self::$globalMiddlewareHandler, $action, $args, $next);
        }

        $values = array_values($args);
        $values[] = $next;

        return call_user_func_array($action, $values);
    }

    /**
     * Deletes all defined routes and middleware from router.
     *
     * @return void
     */
    public function clear()
    {
        $this->routes = [];
        $this->middleware = [];
    }
}
