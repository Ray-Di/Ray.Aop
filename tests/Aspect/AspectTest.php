<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Aspect\Fake\src\FakeMyClass;
use Ray\Aop\Matcher\AnyMatcher;
use Ray\Aop\Matcher\StartsWithMatcher;

use function get_class;

class AspectTest extends TestCase
{
    /** @var Aspect */
    private $aspect;

    protected function setUp(): void
    {
        $this->aspect = new Aspect();
    }

    public function testNewInstance(): void
    {
        $this->aspect->bind(
            new AnyMatcher(),
            new StartsWithMatcher('my'),
            [new FakeMyInterceptor()]
        );
        $myClass = $this->aspect->newInstance(FakeMyClass::class);
        $this->assertNotSame(get_class($myClass), FakeMyClass::class);
        $result = $myClass->myMethod();
        // the original method is intercepted
        $this->assertEquals('intercepted original', $result);
    }

    public function testNewInstanceWithNoBound(): void
    {
        $insntance = $this->aspect->newInstance(FakeMyClass::class);
        $this->assertInstanceOf(FakeMyClass::class, $insntance);
    }

    /**
     * @requires extension rauoop
     * @requires PHP 8.1
     * @depends testNewInstance
     *
     * Don't use runInSeparateProcess. PECL AOP extension is not loaded in the separate process.
     */
    public function testWeave(): void
    {
        $this->aspect->bind(
            new AnyMatcher(),
            new StartsWithMatcher('my'),
            [new FakeMyInterceptor()]
        );
        $this->aspect->weave(__DIR__ . '/Fake/src');
        // here we are testing the interception!
        $myClass = new FakeMyClass();
        $result = $myClass->myMethod();
        $this->assertSame(get_class($myClass), FakeMyClass::class);
        // the original method is intercepted
        $this->assertEquals('intercepted original', $result);
    }
}
