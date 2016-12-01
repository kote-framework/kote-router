<?php

namespace Nerd\Framework\Routing;

use Nerd\Framework\Http\Request\RequestContract;
use Nerd\Framework\Routing\Route\Matcher\MatcherBuilder;
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
     * Add route into routes list.
     *
     * @param string|array $methods
     * @param string $pattern
     * @param callable[] ...$actions
     * @return RouteContract
     */
    public function add(array $methods, string $pattern, callable ...$actions): RouteContract
    {
        $actionSize = sizeof($actions);

        if ($actionSize == 0) {
            throw new \BadMethodCallException("Action need to be specified.");
        }

        $action = $actions[$actionSize - 1];
        $middleware = array_slice($actions, 0, $actionSize - 1);

        $matcher = $this->matcherBuilder->build($pattern);
        $route = new Route($matcher, $middleware, $action);

        foreach ($methods as $method) {
            if (!array_key_exists($method, $this->routes)) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = $route;
        }

        return $route;
    }

    /**
     * Add route for GET method into routes list.
     *
     * @param string $pattern
     * @param callable[] ...$action
     * @return RouteContract
     */
    public function get(string $pattern, callable ...$action): RouteContract
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["HEAD", "GET"], $matcher, ...$action);
    }

    /**
     * Add route for POST method into routes list.
     *
     * @param string $pattern
     * @param callable[] ...$action
     * @return Router
     */
    public function post(string $pattern, callable ...$action): RouteContract
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["POST"], $matcher, ...$action);
    }

    /**
     * Add route for PUT method into routes list.
     *
     * @param string $pattern
     * @param callable[] ...$action
     * @return RouteContract
     */
    public function put(string $pattern, callable ...$action): RouteContract
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["PUT"], $matcher, ...$action);
    }

    /**
     * Add route for DELETE method into routes list.
     *
     * @param string $pattern
     * @param callable[] ...$action
     * @return RouteContract
     */
    public function delete(string $pattern, callable ...$action): RouteContract
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(["DELETE"], $matcher, ...$action);
    }

    /**
     * @param string $pattern
     * @param callable[] ...$action
     * @return RouteContract
     */
    public function any(string $pattern, callable ...$action): RouteContract
    {
        $matcher = $this->matcherBuilder->build($pattern);

        return $this->add(self::$availableMethods, $matcher, ...$action);
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

        return $this->cascadeMiddlewareWithRoute($route, $request);
    }

    /**
     * Cascades middleware list with route action.
     *
     * @param RouteContract $route
     * @param RequestContract $request
     * @return mixed
     */
    private function cascadeMiddlewareWithRoute(RouteContract $route, RequestContract $request)
    {
        $invokeRoute = function ($route, $request) {
            return $this->invokeRoute($route, $request);
        };

        $action = array_reduce(array_reverse($route->getMiddleware()), function ($first, $second) {
            return function (RouteContract $route, RequestContract $request) use ($first, $second) {
                return $this->invokeMiddleware($second, $route, $request, $first);
            };
        }, $invokeRoute);

        return $action($route, $request);
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
     * @param RouteContract $route
     * @param RequestContract $request
     * @param callable $next
     * @return mixed
     */
    private function invokeMiddleware(callable $action, RouteContract $route, RequestContract $request, callable $next)
    {
        if (is_callable(self::$globalMiddlewareHandler)) {
            return call_user_func(self::$globalMiddlewareHandler, $action, $route, $request, $next);
        }

        return call_user_func($action, $route, $request, $next);
    }

    /**
     * Deletes all defined routes and middleware from router.
     *
     * @return void
     */
    public function clear()
    {
        $this->routes = [];
    }
}
