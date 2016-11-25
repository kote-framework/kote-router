<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:37 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

use function Nerd\Lambda\l;

class RegexRouteMatcher implements RoutePatternMatcherContract
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
        $this->route = "~^$route$~";
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
            $this->saveToCache($route, $this->filterArgs(array_slice($args, 1)));
        } else {
            $this->saveToCache($route, null);
        }
    }

    /**
     * Filter arguments after regexp matching.
     *
     * @param array $args
     * @return array
     */
    private function filterArgs(array $args): array
    {
        $isNumeric = array_reduce(array_keys($args), l('$ && is_int($)'), true);

        $filter = $isNumeric ? "is_int" : "is_string";

        return array_filter($args, $filter, ARRAY_FILTER_USE_KEY);
    }
}
