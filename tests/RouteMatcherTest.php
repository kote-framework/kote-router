<?php
/**
 * Created by PhpStorm.
 * User: roman
 * Date: 11/25/16
 * Time: 10:25 PM
 */

namespace tests;

use Nerd\Framework\Routing\Route\Matcher\ExtendedMatcher;
use Nerd\Framework\Routing\Route\Matcher\FastMatcher;
use Nerd\Framework\Routing\Route\Matcher\MatcherBuilder;
use Nerd\Framework\Routing\Route\Matcher\RegexMatcher;
use Nerd\Framework\Routing\Route\Matcher\StaticMatcher;

use PHPUnit\Framework\TestCase;

class RouteMatcherTest extends TestCase
{
    public function testSimpleMatcher()
    {
        $matcher = new StaticMatcher('/');

        $this->assertTrue($matcher->matches('/'));
        $this->assertFalse($matcher->matches('other'));

        $this->assertEquals([], $matcher->extractParameters('/'));
    }

    public function testFastMatcher()
    {
        $matcher = new FastMatcher('users/:userId');

        $this->assertTrue($matcher->matches('users/bill'));
        $this->assertEquals(['userId' => 'bill'], $matcher->extractParameters('users/bill'));

        $this->assertFalse($matcher->matches('/'));
        $this->assertFalse($matcher->matches('users/bill/other'));
        $this->assertFalse($matcher->matches('images'));

        $otherMatcher = new FastMatcher('users/:userId/images/&imageId');

        $this->assertTrue($otherMatcher->matches('users/bob/images/11'));
        $this->assertFalse($otherMatcher->matches('users/bob/images/string'));
        $this->assertFalse($otherMatcher->matches('users/bob/images/'));
    }

    public function testRegexMatcher()
    {
        $matcher = new RegexMatcher('users/(.+)');

        $this->assertTrue($matcher->matches('users/bill'));
        $this->assertEquals(['bill'], $matcher->extractParameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));

        $otherMatcher = new RegexMatcher('users/(?P<userId>.+)');

        $this->assertTrue($otherMatcher->matches('users/bill'));
        $this->assertEquals(['userId' => 'bill'], $otherMatcher->extractParameters('users/bill'));
    }

    public function testExtendedMatcher()
    {
        $matcher = new ExtendedMatcher('users/:id');

        $this->assertTrue($matcher->matches('users/bill'));
        $this->assertEquals(['id' => 'bill'], $matcher->extractParameters('users/bill'));

        $this->assertFalse($matcher->matches('other/route'));


        $numericMatcher = new ExtendedMatcher('users/&userId');

        $this->assertTrue($numericMatcher->matches('users/100'));
        $this->assertFalse($numericMatcher->matches('users/sam'));

        $multiParameterMatcher = new ExtendedMatcher('items/&id-:name');

        $this->assertTrue($multiParameterMatcher->matches('items/15-something'));
        $this->assertEquals(
            ['id' => '15', 'name' => 'something'],
            $multiParameterMatcher->extractParameters('items/15-something')
        );
    }

    public function testMatcherBuilder()
    {
        $builder = new MatcherBuilder();

        $this->assertInstanceOf(StaticMatcher::class, $builder->build('/'));
        $this->assertInstanceOf(StaticMatcher::class, $builder->build('route/static'));
        $this->assertInstanceOf(FastMatcher::class, $builder->build('user/:userId'));
        $this->assertInstanceOf(ExtendedMatcher::class, $builder->build('user/:userId-:otherId'));
        $this->assertInstanceOf(RegexMatcher::class, $builder->build('~^user/(/d+)$~'));
    }
}
