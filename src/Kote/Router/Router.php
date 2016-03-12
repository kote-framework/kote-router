<?php

namespace Kote\Router;


class Router
{
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
     * Route handler which will be invoked when router find route matching request.
     *
     * @var callable
     */
    private static $globalHandler;

    /**
     * Sets global route handler.
     *
     * @param callable $globalHandler
     */
    public static function setGlobalHandler($globalHandler)
    {
        self::$globalHandler = $globalHandler;
    }

    /**
     * Adds middleware to router.
     *
     * @param $pathRegExp
     * @param $middleware
     * @return Router
     */
    public function addMiddleware($pathRegExp, $middleware)
    {
        $this->middleware[] = ["~^$pathRegExp$~i", $middleware];

        return $this;
    }

    /**
     * Adds route into routes list.
     *
     * @param string|array $methods
     * @param string $pathRegExp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function add($methods, $pathRegExp, $action, $data = null)
    {
        if (!is_array($methods)) {
            $methods = [$methods];
        }

        foreach ($methods as $method) {
            if (!isset($this->routes[$method])) {
                $this->routes[$method] = [];
            }

            $this->routes[$method][] = ["~^$pathRegExp$~i", $action, $data];
        }

        return $this;
    }

    /**
     * Adds route for GET method into routes list.
     *
     * @param string $pathRegExp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function get($pathRegExp, $action, $data = null)
    {
        return $this->add(["HEAD", "GET"], $pathRegExp, $action, $data);
    }

    /**
     * Adds route for POST method into routes list.
     *
     * @param string $pathRegExp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function post($pathRegExp, $action, $data = null)
    {
        return $this->add("POST", $pathRegExp, $action, $data);
    }

    /**
     * Adds route for PUT method into routes list.
     *
     * @param string $pathRegExp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function put($pathRegExp, $action, $data = null)
    {
        return $this->add("PUT", $pathRegExp, $action, $data);
    }

    /**
     * Adds route for DELETE method into routes list.
     *
     * @param string $pathRegExp
     * @param callable $action
     * @param mixed $data
     * @return Router
     */
    public function delete($pathRegExp, $action, $data = null)
    {
        return $this->add("DELETE", $pathRegExp, $action, $data);
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
        if (!isset($this->routes[$method])) {
            throw new MethodNotImplementedException("Method $method not implemented.");
        }

        $matching = $this->findMatchingRoutes($method, $path);

        if (count($matching) > 0) {

            $route = $this->getBestRoute($matching);
            $middleware = $this->findMiddleware($path);

            $action = $this->cascadeMiddlewareWithRoute($middleware, $route);

            return $action();
        }

        throw new RouteNotFoundException("Document $path not found on this server.");
    }

    /**
     * Cascades middleware list with route action.
     *
     * @param array $middleware
     * @param callable $route
     * @return callable
     */
    private function cascadeMiddlewareWithRoute($middleware, $route)
    {
        $actionInvoker = function () use ($route) { return $this->call(...$route); };

        $action = array_reduce(array_reverse($middleware), function ($first, $second) {
            return function () use ($first, $second) {
                list ($middleware, $args) = $second;
                return $middleware($first, ...array_values($args));
            };
        }, $actionInvoker);

        return $action;
    }

    /**
     * Gets list of routes matching request.
     *
     * @param $method
     * @param $path
     * @return array
     */
    private function findMatchingRoutes($method, $path)
    {
        $matching = [];
        foreach ($this->routes[$method] as $route) {
            list ($regexp, $action, $data) = $route;
            if (preg_match($regexp, $path, $args)) {
                array_shift($args);
                $matching[$regexp] = [$action, $args, $data];
            }
        }

        return $matching;
    }

    /**
     * Gets best route from list of matching routes.
     *
     * @param $routes
     * @return mixed
     */
    private function getBestRoute($routes)
    {
        $longestKey = "";
        $longestKeyLength = 0;
        foreach (array_keys($routes) as $key) {
            if ($longestKeyLength < $len = strlen($key)) {
                $longestKey = $key;
                $longestKeyLength = $len;
            }
        }

        return $routes[$longestKey];
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
            list ($regexp, $action) = $item;
            if (preg_match($regexp, $path, $args)) {
                array_shift($args);
                $middleware[] = [$action, $args];
            }
        }

        return $middleware;
    }

    /**
     * Finds action matching current HTTP request and invoke it.
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
    private function call($action, array $args, $data)
    {
        if (is_callable(self::$globalHandler)) {
            return call_user_func(self::$globalHandler, $action, $args, $data);
        }

        elseif (is_callable($action)) {
            return $action(...array_values($args));
        }

        throw new RouterException("No valid route handler found.");
    }
}