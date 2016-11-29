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
    private $name = null;

    /**
     * @var Matcher
     */
    private $matcher;

    /**
     * @var callable
     */
    private $action;

    /**
     * @var array
     */
    private $middleware;


    /**
     * @param Matcher $matcher
     * @param array $middleware
     * @param callable $action
     */
    public function __construct(Matcher $matcher, array $middleware, callable $action)
    {
        $this->matcher = $matcher;
        $this->middleware = $middleware;
        $this->action = $action;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return callable
     */
    public function getAction(): callable
    {
        return $this->action;
    }

    /**
     * @return array
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
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
