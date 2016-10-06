<?php

namespace tests\fixtures;

use Nerd\Framework\Http\RequestContract;

class TestRequest implements RequestContract
{
    private $method;
    private $path;

    /**
     * @param $method
     * @param $path
     */
    private function __construct($method, $path)
    {
        $this->method = $method;
        $this->path = $path;
    }

    public static function make($method, $path)
    {
        return new static($method, $path);
    }

    /**
     * Get HTTP Request method.
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Test that $method equals to HTTP Request method.
     *
     * @param string $method
     * @return mixed
     */
    public function isMethod($method)
    {
        return 0 === strcasecmp($method, $this->getMethod());
    }

    /**
     * Get HTTP Request path.
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Get server parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getServerParameter($key, $default = null)
    {
        return $default;
    }

    /**
     * Get query string parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getQueryParameter($key, $default = null)
    {
        return $default;
    }

    /**
     * Get post parameter.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPostParameter($key, $default = null)
    {
        return $default;
    }

    /**
     * Get cookie.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getCookie($key, $default = null)
    {
        return $default;
    }
}
