<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 9:28 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

use Nerd\Framework\Routing\RouterException;

trait RoutePatternMatcherTrait
{
    /**
     * @var array
     */
    private $cache;

    /**
     * @param string $route
     * @param array|null $parameters
     */
    public function saveToCache(string $route, array $parameters = null)
    {
        $this->cache[$route] = $parameters;
    }

    /**
     * @param string $route
     * @return bool
     */
    public function isCached(string $route): bool
    {
        return array_key_exists($route, $this->cache);
    }

    /**
     * @param string $route
     * @return bool
     */
    public function isMatched(string $route): bool
    {
        return is_array($this->cache[$route]);
    }

    /**
     * @return string
     */
    abstract protected function getRoute();

    /**
     * @param string $route
     * @return void
     */
    abstract protected function match(string $route);

    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): boolean
    {
        $this->match($route);

        return $this->isMatched($route);
    }

    /**
     * @param string $route
     * @return array
     * @throws RouterException
     */
    public function parameters(string $route): array
    {
        $this->match($route);

        if (!$this->isMatched($route)) {
            throw new RouterException("Can't extract parameters from unmatched route.");
        }

        return $this->cache[$route];
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getRoute();
    }
}
