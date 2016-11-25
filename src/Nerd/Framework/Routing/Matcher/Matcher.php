<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:41 PM
 */

namespace Nerd\Framework\Routing\Matcher;

/**
 * @param string $route
 * @return RouteMatcher
 */
function regex(string $route): RouteMatcher
{
    return new RegexRouteMatcher($route);
}

/**
 * @param string $route
 * @return RouteMatcher
 */
function plain(string $route): RouteMatcher
{
    return new PlainRouteMatcher($route);
}
