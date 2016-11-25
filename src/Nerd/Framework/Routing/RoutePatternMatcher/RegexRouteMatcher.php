<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:37 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

use Nerd\Framework\Routing\RouterException;

class RegexRouteMatcher implements RoutePatternMatcher
{
    use RoutePatternMatcherTrait;

    /**
     * @var string
     */
    private $route;

    /**
     * PregRouteMatcher constructor.
     * @param $route
     */
    public function __construct(string $route)
    {
        $this->route = "/^$route$/";
    }

    /**
     * @return string
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @param string $route
     * @return void
     */
    protected function match(string $route)
    {
        if ($this->isCached($route)) {
            return;
        }

        if (preg_match($this->route, $route, $args)) {
            $this->saveToCache($route, array_slice($args, 1));
        } else {
            $this->saveToCache($route, null);
        }
    }
}
