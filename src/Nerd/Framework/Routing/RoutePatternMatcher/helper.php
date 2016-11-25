<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:41 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

/**
 * @param string $route
 * @return RoutePatternMatcher
 */
function regex(string $route): RoutePatternMatcher
{
    return new RegexRouteMatcher($route);
}

/**
 * @param string $route
 * @return RoutePatternMatcher
 */
function plain(string $route): RoutePatternMatcher
{
    return new PlainRouteMatcher($route);
}

/**
 * @param string $route
 * @return RoutePatternMatcher
 */
function fast(string $route): RoutePatternMatcher
{
    return null;
}
