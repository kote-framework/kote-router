<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:37 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;


class RegexRouteMatcher implements RoutePatternMatcher
{
    private $route;

    /**
     * PregRouteMatcher constructor.
     * @param $route
     */
    public function __construct(string $route)
    {
        $this->route = "~^$route$~";
    }

    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): boolean
    {
        return (boolean) preg_match($this->route, $route);
    }

    /**
     * @param string $route
     * @return array
     */
    public function parameters(string $route): array
    {
        preg_match($this->route, $route, $args);

        return array_slice($args, 1);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->route;
    }
}
