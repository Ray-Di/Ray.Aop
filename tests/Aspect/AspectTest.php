<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Annotation\FakeClassMarker;
use Ray\Aop\Annotation\FakeMarker;
use Ray\Aop\Exception\NotWritableException;
use Ray\Aop\Matcher\AnyMatcher;
use Ray\Aop\Matcher\StartsWithMatcher;

use function dirname;
use function get_class;

class AspectTest extends TestCase
{
    /** @var Aspect */
    private $aspect;

    protected function setUp(): void
    {
        $this->aspect = new Aspect();
    }

    public function testTmpDir(): void
    {
        $this->assertInstanceOf(Aspect::class, new Aspect(dirname(__DIR__) . '/tmp'));
    }

    public function testTmpDirNotWritable(): void
    {
        $this->expectException(NotWritableException::class);
        new Aspect('/__INVALID_DIR__');
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
     * @requires extension rayaop
     * @requires PHP 8.1
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
        $this->aspect->weave(__DIR__ . '/Fake');
        // here we are testing the interception!
        $myClass = new FakePeclClass();
        $result = $myClass->myMethod();
        $this->assertSame(get_class($myClass), FakePeclClass::class);
        // the original method is intercepted
        $this->assertEquals('intercepted original', $result);
    }

    /**
     * @requires extension rayaop
     * @requires PHP 8.1
     * @depends testWeave
     */
    public function testWeaveFinalClass(): void
    {
        $myClass = new FakeFinalClass();
        $result = $myClass->myMethod();
        // the original method is intercepted
        $this->assertEquals('intercepted original', $result);
    }

    public function testAnnotateMatcher(): void
    {
        $aspect = new Aspect();
        $aspect->bind(
            (new Matcher())->annotatedWith(FakeClassMarker::class),
            (new Matcher())->any(),
            [new FakeMyInterceptor()]
        );

        $billing = $aspect->newInstance(FakeMyClass::class);
        $this->assertInstanceOf(FakeMyClass::class, $billing);
    }

    /**
     * @requires extension rayaop
     * @requires PHP 8.1
     */
    public function testNotClassMatch(): void
    {
        $aspect = new Aspect();
        $aspect->bind(
            (new Matcher())->annotatedWith(FakeMarker::class), // not match
            (new Matcher())->any(),
            [new FakeMyInterceptor()]
        );
        $aspect->weave(__DIR__ . '/Fake');
        $billing = $aspect->newInstance(FakeMyClass::class);
        $this->assertInstanceOf(FakeMyClass::class, $billing);
    }

    /**
     * @requires extension rayaop
     * @requires PHP 8.1
     */
    public function testNotMethodMatch(): void
    {
        $aspect = new Aspect();
        $aspect->bind(
            (new Matcher())->any(),
            (new Matcher())->annotatedWith(FakeMarker::class), // not match
            [new FakeMyInterceptor()]
        );
        $aspect->weave(__DIR__ . '/Fake/src');
        $billing = $aspect->newInstance(FakeMyClass::class);
        $this->assertInstanceOf(FakeMyClass::class, $billing);
    }
}
