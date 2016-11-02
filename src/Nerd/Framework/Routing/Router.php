<?php

namespace Nerd\Framework\Routing;

use Nerd\Framework\Http\Request\RequestContract;
use Nerd\Framework\Http\Response\ResponseContract;

use function Nerd\Lambda\l;

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
     * @var array
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
     * @param string $regexp
     * @param callable $middleware
     * @return Router
     */
    public function middleware(string $regexp, callable $middleware)
    {
        $this->validateUrlPattern($regexp);

        $preparedRoute = $this->prepareRoute($regexp);

        $this->middleware[] = ["~^$preparedRoute$~i", $middleware];

        return $this;
    }

    /**
     * Add route into routes list.
     *
     * @param string|array $methods
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function add(array $methods, string $regexp, callable $action, $data = null)
    {
        $this->validateUrlPattern($regexp);

        $updatedRoute = $this->prepareRoute($regexp);

        foreach ($methods as $method) {
            if (!array_key_exists($method, $this->routes)) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = ["~^$updatedRoute$~i", $action, $data];
        }

        return $this;
    }

    /**
     * @param string $regexp
     * @return void
     * @throws RouterException
     */
    private function validateUrlPattern(string $regexp)
    {
        if ($regexp != "/" && substr($regexp, 0, 1) == "/") {
            throw new RouterException("Url pattern must not begin with a slash.");
        }
    }

    /**
     * @param string $route
     * @return string
     */
    private function prepareRoute(string $route): string
    {
        $updatedRoute = $this->quoteRoute($route);
        $updatedRoute = preg_replace('/:([^\/]+)/', '(?P<$1>[\w-]+)', $updatedRoute);
        $updatedRoute = preg_replace('/&([^\/]+)/', '(?P<$1>[\d]+)', $updatedRoute);

        return $updatedRoute;
    }

    /**
     * Quote special symbols to ignore it by regular expression matcher.
     *
     * @param string $route
     * @return string
     */
    private function quoteRoute(string $route): string
    {
        $specialSymbols = '.\\/+*?[^]$(){}=!<>|-';

        return implode(array_map(function ($char) use ($specialSymbols) {
            return strpos($specialSymbols, $char) === false ? $char : '\\' . $char;
        }, str_split($route)));
    }

    /**
     * Add route for GET method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function get(string $regexp, callable $action, $data = null)
    {
        return $this->add(["HEAD", "GET"], $regexp, $action, $data);
    }

    /**
     * Add route for POST method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function post(string $regexp, callable $action, $data = null)
    {
        return $this->add(["POST"], $regexp, $action, $data);
    }

    /**
     * Add route for PUT method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function put(string $regexp, callable $action, $data = null)
    {
        return $this->add(["PUT"], $regexp, $action, $data);
    }

    /**
     * Add route for DELETE method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function delete(string $regexp, callable $action, $data = null)
    {
        return $this->add(["DELETE"], $regexp, $action, $data);
    }

    /**
     * @param string $regexp
     * @param callable $action
     * @param null $data
     * @return Router
     */
    public function any(string $regexp, callable $action, $data = null)
    {
        return $this->add(self::$availableMethods, $regexp, $action, $data);
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

        return $this->cascadeMiddlewareWithRoute($middleware, $route);
    }

    /**
     * Cascades middleware list with route action.
     *
     * @param array $middleware
     * @param array $route
     * @return mixed
     */
    private function cascadeMiddlewareWithRoute(array $middleware, array $route)
    {
        $invokeRoute = function () use ($route) {
            return $this->invokeRoute(...$route);
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
     * @return array
     */
    private function findMatchingRoute(RequestContract $request)
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        if (!array_key_exists($method, $this->routes)) {
            return null;
        }

        foreach ($this->routes[$method] as list($regexp, $action, $data)) {
            if (preg_match($regexp, $path, $args)) {
                return [$action, $this->filterArgs(array_slice($args, 1)), $data];
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

        foreach ($this->middleware as list($regexp, $action)) {
            if (preg_match($regexp, $path, $args)) {
                $middleware[] = [$action, $this->filterArgs(array_slice($args, 1))];
            }
        }

        return $middleware;
    }

     /**
     * @param callable $action
     * @param array $args
     * @param mixed $data
     * @return mixed
     * @throws RouterException
     */
    private function invokeRoute(callable $action, array $args, $data)
    {
        if (is_callable(self::$globalRouteHandler)) {
            return call_user_func(self::$globalRouteHandler, $action, $args, $data);
        }

        return call_user_func_array($action, array_values($args));
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

    /**
     * Filter arguments after regexp matching.
     *
     * @param array $args
     * @return array
     */
    public function filterArgs(array $args): array
    {
        $isNumeric = array_reduce(array_keys($args), l('$ && is_int($)'), true);

        $filter = $isNumeric ? "is_int" : "is_string";

        return array_filter($args, $filter, ARRAY_FILTER_USE_KEY);
    }
}
