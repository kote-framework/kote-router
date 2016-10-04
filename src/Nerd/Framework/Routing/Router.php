<?php

namespace Nerd\Framework\Routing;

class Router
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
     * Sets global route handler.
     *
     * @param callable $globalRouteHandler
     */
    public static function setGlobalRouteHandler($globalRouteHandler)
    {
        self::$globalRouteHandler = $globalRouteHandler;
    }

    /**
     * Sets global middleware handler.
     *
     * @param callable $globalMiddlewareHandler
     */
    public static function setGlobalMiddlewareHandler($globalMiddlewareHandler)
    {
        self::$globalMiddlewareHandler = $globalMiddlewareHandler;
    }

    /**
     * Adds middleware to router.
     *
     * @param $regexp
     * @param $middleware
     * @return Router
     */
    public function middleware($regexp, $middleware)
    {
        $this->validateUrlPattern($regexp);
        
        $this->middleware[] = ["~^$regexp$~i", $middleware];

        return $this;
    }

    /**
     * Adds route into routes list.
     *
     * @param string|array $methods
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function add($methods, $regexp, $action, $data = null)
    {
        $this->validateUrlPattern($regexp);
        
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            if (!isset($this->routes[$method])) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = ["~^$regexp$~i", $action, $data];
        }

        return $this;
    }

    /**
     * @param string $regexp
     * @return void
     * @throws RouterException
     */
    private function validateUrlPattern($regexp)
    {
        if ($regexp != "/" && substr($regexp, 0, 1) == "/") {
            throw new RouterException("Url pattern must not begin with a slash.");
        }
    }

    /**
     * Adds route for GET method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function get($regexp, $action, $data = null)
    {
        return $this->add(["HEAD", "GET"], $regexp, $action, $data);
    }

    /**
     * Adds route for POST method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function post($regexp, $action, $data = null)
    {
        return $this->add("POST", $regexp, $action, $data);
    }

    /**
     * Adds route for PUT method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function put($regexp, $action, $data = null)
    {
        return $this->add("PUT", $regexp, $action, $data);
    }

    /**
     * Adds route for DELETE method into routes list.
     *
     * @param string $regexp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function delete($regexp, $action, $data = null)
    {
        return $this->add("DELETE", $regexp, $action, $data);
    }

    /**
     * @param string $regexp
     * @param callable $action
     * @param null $data
     * @return Router
     */
    public function any($regexp, $action, $data = null)
    {
        return $this->add(self::$availableMethods, $regexp, $action, $data);
    }

    /**
     * Finds action matching HTTP request $method and $path and invoke it.
     *
     * @param string $method
     * @param string $path
     * @return mixed
     * @throws RouterException
     */
    public function handle($method, $path)
    {
        $route = $this->findMatchingRoute($method, $path);

        if (is_null($route)) {
            throw new RouteNotFoundException("Document $path not found on this server.");
        }

        $middleware = $this->findMiddleware($path);

        return $this->cascadeMiddlewareWithRoute($middleware, $route);
    }

    /**
     * Cascades middleware list with route action.
     *
     * @param array $middleware
     * @param callable $route
     * @return mixed
     */
    private function cascadeMiddlewareWithRoute($middleware, $route)
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
     * @param $method
     * @param $path
     * @return array
     */
    private function findMatchingRoute($method, $path)
    {
        foreach ($this->routes[$method] as $route) {
            list($regexp, $action, $data) = $route;
            if (preg_match($regexp, $path, $args)) {
                return [$action, $this->filterArgs($args), $data];
            }
        }

        return null;
    }

    /**
     * Filter arguments after regexp matching.
     *
     * @param array $args
     * @return array
     */
    private function filterArgs(array $args)
    {
        $result = [];
        $previous = null;

        foreach ($args as $key => $arg) {
            if (is_int($previous)) {
                if (is_int($key)) {
                    $result[] = $arg;
                } else {
                    $result[$key] = $arg;
                }
            }
            $previous = $key;
        }

        return $result;
    }

    /**
     * Find middleware matching given path.
     *
     * @param $path
     * @return array
     */
    private function findMiddleware($path)
    {
        $middleware = [];

        foreach ($this->middleware as $item) {
            list($regexp, $action) = $item;
            if (preg_match($regexp, $path, $args)) {
                $middleware[] = [$action, $this->filterArgs($args)];
            }
        }

        return $middleware;
    }

    /**
     * Find action matching current HTTP request and invoke it.
     *
     * @return mixed
     * @throws RouterException
     */
    public function run()
    {
        $method = $_SERVER["REQUEST_METHOD"];
        $path = ltrim(urldecode(parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)), "/") ?: "/";

        return $this->handle($method, $path);
    }

    /**
     * @param callable $action
     * @param array $args
     * @param mixed $data
     * @return mixed
     * @throws RouterException
     */
    private function invokeRoute($action, array $args, $data)
    {
        if (is_callable(self::$globalRouteHandler)) {
            return call_user_func(self::$globalRouteHandler, $action, $args, $data);
        }

        if (is_callable($action)) {
            return call_user_func_array($action, array_values($args));
        }

        throw new RouterException("Invalid route handler.");
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

        if (is_callable($action)) {
            $values = array_values($args);
            $values[] = $next;
            return call_user_func_array($action, $values);
        }

        throw new RouterException("Invalid middleware handler.");
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
