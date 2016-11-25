<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 10:25 PM
 */

namespace tests;

use function Nerd\Framework\Routing\RoutePatternMatcher\fast;
use function Nerd\Framework\Routing\RoutePatternMatcher\plain;
use function Nerd\Framework\Routing\RoutePatternMatcher\regex;

use PHPUnit\Framework\TestCase;

class RoutePatternMatcherTest extends TestCase
{
    public function testRegexRouteMatcher()
    {
        $matcher = regex('users/(.+)');

        $this->assertTrue($matcher->matches('users/bill'));

        $this->assertEquals(['bill'], $matcher->parameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));

        $this->assertNull($matcher->parameters('other/route'));
    }

    public function testPlainRouteMatcher()
    {
        $matcher = plain('users/:id');

        $this->assertTrue($matcher->matches('users/bill'));

        $this->assertEquals(['id' => 'bill'], $matcher->parameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));

        $this->assertNull($matcher->parameters('other/route'));
    }

    public function testFastRouteMatcher()
    {
        $matcher = fast('users/:id');

        $this->assertEquals('users/:id', (string) $matcher);

        $this->assertTrue($matcher->matches('users/bill'));

        $this->assertEquals(['id' => 'bill'], $matcher->parameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));

        $this->assertNull($matcher->parameters('other/route'));
    }

    public function testMultipleArguments()
    {
        $matcher = plain('users/:userId/images/:imageId');

        $this->assertTrue($matcher->matches('users/bill/images/picture'));

        $this->assertEquals(['userId' => 'bill', 'imageId' => 'picture'], $matcher->parameters('users/bill/images/picture'));
    }
}
