<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/27/16
 * Time: 12:40 PM
 */

namespace Nerd\Framework\Routing\Route;

use Nerd\Framework\Container\ContainerContract;
use Nerd\Framework\Routing\Route\Matcher\MatcherContract as Matcher;
use Nerd\Framework\Http\Request\RequestContract as Request;
use Nerd\Framework\Container\ContainerContract as Container;

class Route implements RouteContract
{
    private $matcher;

    private $action;

    private $data;

    public function __construct(Matcher $matcher, callable $action, $data = null)
    {
        $this->matcher = $matcher;
        $this->action = $action;
        $this->data = $data;
    }

    /**
     * @return callable
     */
    public function getAction(): callable
    {
        return $this->action;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function matches(Request $request): bool
    {
        return $this->matcher->matches($request->getPath());
    }

    /**
     * @param Request $request
     * @return array
     */
    public function parameters(Request $request): array
    {
        return $this->matcher->extractParameters($request->getPath());
    }
}
