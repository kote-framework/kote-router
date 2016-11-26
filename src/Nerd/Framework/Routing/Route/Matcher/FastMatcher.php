<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/26/16
 * Time: 4:57 PM
 */

namespace Nerd\Framework\Routing\Route\Matcher;

use Nerd\Framework\Routing\RouterException;

class FastMatcher extends Matcher
{
    /**
     * @var array
     */
    private $patternParts;

    /**
     * @param string $route
     */
    public function __construct(string $route)
    {
        $this->patternParts = $this->getRouteParts($route);

        parent::__construct($route);
    }

    /**
     * @param string $route
     * @return bool
     */
    public function matches(string $route): bool
    {
        $routeParts = $this->getRouteParts($route);

        if (sizeof($this->patternParts) != sizeof($routeParts)) {
            return false;
        }

        for ($i = 0; $i < sizeof($this->patternParts); $i++) {
            $patternPart = $this->patternParts[$i];
            $routePart   = $routeParts[$i];

            $isNumeric = $this->isNumberParameter($patternPart) & is_numeric($routePart);
            $isString  = $this->isStringParameter($patternPart);

            if ($isNumeric || $isString) {
                continue;
            }

            if ($routePart != $patternPart) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $route
     * @return array
     */
    public function extractParameters(string $route): array
    {
        $routeParts = $this->getRouteParts($route);

        $parameters = [];

        for ($i = 0; $i < sizeof($this->patternParts); $i++) {
            $patternPart = $this->patternParts[$i];
            $routePart   = $routeParts[$i];

            $isNumeric = $this->isNumberParameter($patternPart) & is_numeric($routePart);
            $isString  = $this->isStringParameter($patternPart);

            if ($isNumeric || $isString) {
                $parameterName = substr($patternPart, 1);
                $parameters[$parameterName] = $routePart;
            } elseif ($routeParts[$i] != $this->patternParts[$i]) {
                return [];
            }
        }

        return $parameters;
    }

    /**
     * @param string $route
     * @return array
     * @throws RouterException
     */
    private function getRouteParts(string $route): array
    {
        if (strlen($route) == 0 || $route == '/') {
            return ['/'];
        }

        return explode('/', $route);
    }

    /**
     * @param string $part
     * @return bool
     */
    private function isNumberParameter(string $part): bool
    {
        return $part[0] == '&';
    }

    /**
     * @param string $part
     * @return bool
     */
    private function isStringParameter(string $part): bool
    {
        return $part[0] == ':';
    }
}
