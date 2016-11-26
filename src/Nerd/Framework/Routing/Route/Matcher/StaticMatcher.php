<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 4:43 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

class StaticMatcher extends Matcher
{
    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): bool
    {
        return $route == $this->route;
    }

    /**
     * @param string $route
     * @return array
     */
    public function extractParameters(string $route): array
    {
        return [];
    }
}
