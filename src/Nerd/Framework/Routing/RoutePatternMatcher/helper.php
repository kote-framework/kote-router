<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:41 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

use Nerd\Framework\Routing\RoutePatternMatcher\RoutePatternMatcherContract as Matcher;

/**
 * @param string $route
 * @return Matcher
 */
function regex(string $route): Matcher
{
    return new RegexRouteMatcher($route);
}

/**
 * @param string $route
 * @return Matcher
 */
function plain(string $route): Matcher
{
    return new PlainRouteMatcher($route);
}

/**
 * @param string $route
 * @return Matcher
 */
function fast(string $route): Matcher
{
    return new FastRouteMatcher($route);
}
