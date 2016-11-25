<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 9:14 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

use Nerd\Framework\Routing\RouterException;

class FastRouteMatcher implements RoutePatternMatcher
{
    use RoutePatternMatcherTrait;

    /**
     * @var string
     */
    private $route;

    /**
     * @param string $route
     */
    public function __construct(string $route)
    {
        $this->route = $route;
    }

    protected function getRoute()
    {
        return $this->route;
    }

    public function match(string $route)
    {
        if ($this->isCached($route)) {
            return;
        }

        $patternParts = explode('/', $this->route);
        $routeParts = explode('/', $route);

        if (sizeof($patternParts) != sizeof($routeParts)) {
            $this->saveToCache($route, null);
            return;
        }

        $layers = array_map(function ($patternPart, $routePart) {
            return [$patternPart, $routePart];
        }, $patternParts, $routeParts);

        $parameters = array_reduce($layers, function ($acc, $layer) {
            if (is_null($acc)) {
                return null;
            }

            list ($patternPart, $routePart) = $layer;

            if (strlen($patternPart) > 1 && $patternPart[0] == ':') {
                return array_merge($acc, [substr($patternPart, 1) => $routePart]);
            }

            if (strlen($patternPart) > 1 && $patternPart[0] == '&') {
                return is_int($patternPart)
                    ? array_merge($acc, [substr($patternPart, 1) => $routePart])
                    : null;
            }

            return $patternPart == $routePart ? $acc : null;
        }, []);

        $this->saveToCache($route, $parameters);
    }
}
