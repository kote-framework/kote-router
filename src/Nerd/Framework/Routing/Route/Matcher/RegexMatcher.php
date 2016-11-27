<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 5:53 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

class RegexMatcher extends Matcher
{
    /**
     * @param string $route
     */
    public function __construct(string $route)
    {
        parent::__construct($route);
    }

    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): bool
    {
        return (bool) preg_match($this->route, $route);
    }

    /**
     * @param string $route
     * @return array
     */
    public function extractParameters(string $route): array
    {
        preg_match($this->route, $route, $args);

        return $this->filterArgs(array_slice($args, 1));
    }

    /**
     * Filter arguments after regexp matching.
     *
     * @param array $args
     * @return array
     */
    private function filterArgs(array $args): array
    {
        $isNumeric = array_reduce(array_keys($args), function ($acc, $item) {
            return $acc && is_int($item);
        }, true);

        $filter = $isNumeric ? "is_int" : "is_string";

        return array_filter($args, $filter, ARRAY_FILTER_USE_KEY);
    }
}
