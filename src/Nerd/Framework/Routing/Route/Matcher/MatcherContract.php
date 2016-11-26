<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 4:36 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

interface MatcherContract
{
    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): bool;

    /**
     * @param string $route
     * @return array
     */
    public function extractParameters(string $route): array;
}
