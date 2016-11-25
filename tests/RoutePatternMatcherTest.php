<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 10:25 PM
 */

namespace tests;

use function Nerd\Framework\Routing\RoutePatternMatcher\regex;

use PHPUnit\Framework\TestCase;

class RoutePatternMatcherTest extends TestCase
{
    public function testRegexRouteMatcher()
    {
        $matcher = regex('users/(.+)');

        $this->assertEquals('~^users/(.+)$~', (string) $matcher);

        $this->assertTrue($matcher->matches('users/bill'));

        $this->assertEquals(['bill'], $matcher->parameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));
    }
}
