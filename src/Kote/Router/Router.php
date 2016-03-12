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

        $matching = [];
        foreach ($this->routes[$method] as $route) {
            list ($regexp, $action, $data) = $route;
            if (preg_match($regexp, $path, $args)) {
                array_shift($args);
                $matching[$regexp] = [$action, $args, $data];
            }
        }

        if (count($matching) > 0) {
            $longestKey = "";
            foreach (array_keys($matching) as $key) {
                if (strcmp($longestKey, $key) < 0) {
                    $longestKey = $key;
                }
            }

            return $this->call(...$matching[$longestKey]);
        }

        throw new RouteNotFoundException("Document $path not found on this server.");
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
            return $action(...$args);
        }

        throw new RouterException("No valid route handler found.");
    }
}