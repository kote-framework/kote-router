<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 4:41 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

use Nerd\Framework\Routing\RouterException;

abstract class Matcher implements MatcherContract
{
    /**
     * @var string
     */
    protected $route;

    /**
     * @param string $route
     * @throws RouterException
     */
    public function __construct(string $route)
    {
        $this->route = $route;
    }

    public function __toString()
    {
        return $this->route;
    }
}
