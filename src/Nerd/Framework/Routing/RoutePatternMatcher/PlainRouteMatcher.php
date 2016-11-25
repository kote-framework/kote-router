<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 5:45 PM
 */

namespace Nerd\Framework\Routing\RoutePatternMatcher;

class PlainRouteMatcher extends RegexRouteMatcher
{
    /**
     * @param string $route
     */
    public function __construct(string $route)
    {
        $quotedRoute    = $this->escapeSpecialSymbols($route);
        $convertedRoute = $this->convertParameters($quotedRoute);

        parent::__construct($convertedRoute);
    }

    /**
     * @param string $route
     * @return string
     */
    private function escapeSpecialSymbols(string $route): string
    {
        $specialSymbols = '.\\/+*?[^]$(){}=!<>|-';

        return implode('', array_map(function ($char) use ($specialSymbols) {
            return strpos($specialSymbols, $char) === false ? $char : '\\' . $char;
        }, str_split($route)));
    }

    /**
     * @param string $route
     * @return string
     */
    private function convertParameters(string $route): string
    {
        $updatedRoute = preg_replace('/:([^\/]+)/', '(?P<$1>[\w-]+)', $route);
        $updatedRoute = preg_replace('/&([^\/]+)/', '(?P<$1>[\d]+)', $updatedRoute);

        return $updatedRoute;
    }
}
