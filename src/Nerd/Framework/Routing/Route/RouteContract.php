<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/27/16
 * Time: 12:27 PM
 */

namespace Nerd\Framework\Routing\Route;

use Nerd\Framework\Http\Request\RequestContract as Request;

interface RouteContract
{
    /**
     * @return callable
     */
    public function getAction(): callable;

    /**
     * @return array
     */
    public function getMiddleware(): array;

    /**
     * @param Request $request
     * @return bool
     */
    public function matches(Request $request): bool;

    /**
     * @param Request $request
     * @return array
     */
    public function parameters(Request $request): array;
}
