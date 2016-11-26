<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 5:44 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

class MatcherBuilder
{
    public function build(string $routePattern): Matcher
    {
        if ($routePattern[0] == '~') {
            return new RegexMatcher(trim($routePattern, '~'));
        }

        if ($this->isRouteWithoutParameters($routePattern)) {
            return new StaticMatcher($routePattern);
        }

        $parts = explode('/', $routePattern);

        foreach ($parts as $part) {
            if (strrpos($part, ':') > 0 || strrpos($part, '&') > 0) {
                return new ExtendedMatcher($routePattern);
            }
        }

        return new FastMatcher($routePattern);
    }

    /**
     * @param string $routePattern
     * @return bool
     */
    private function isRouteWithoutParameters(string $routePattern): bool
    {
        return strpos($routePattern, ':') === false && strpos($routePattern, '&') === false;
    }
}
