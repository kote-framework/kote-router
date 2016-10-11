<?php

namespace Nerd\Framework\Routing\Helper;

use function Nerd\Lambda\l;

/**
 * Filter arguments after regexp matching.
 *
 * @param array $args
 * @return array
 */
function filterArgs(array $args)
{
    $isNumeric = array_reduce(array_keys($args), l('$ && is_int($)'), true);

    $filter = $isNumeric ? "is_int" : "is_string";

    return array_filter($args, $filter, ARRAY_FILTER_USE_KEY);
}
