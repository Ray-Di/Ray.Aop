<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Aspect\Fake\src\FakeMyClass;
use Ray\Aop\Matcher\AnyMatcher;
use Ray\Aop\Matcher\StartsWithMatcher;

/** @requires PHP 8.1 */
class AspectTest extends TestCase
{
    private $aspect;

    protected function setUp(): void
    {
        $this->aspect = new Aspect(__DIR__ . '/Fake/src');
    }

    public function testWeave(): void
    {
        $this->aspect->bind(
            new AnyMatcher(),
            new StartsWithMatcher('my'),
            [new FakeMyInterceptor()]
        );
        $this->aspect->weave();
        // here we are testing the interception!
        $myClass = new FakeMyClass();
        $result = $myClass->myMethod();
        // the original method is intercepted
        $this->assertEquals('intercepted original', $result);
    }
}
