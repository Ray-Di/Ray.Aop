<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Annotation\FakeMarker;
use Ray\Aop\Annotation\FakeMarker2;
use Ray\Aop\Annotation\FakeMarker3;

class BindTest extends TestCase
{
    /** @var Bind */
    protected $bind;

    /** @var array<MethodInterceptor> */
    protected $interceptors;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bind = new Bind();
    }

    public function testBindInterceptors(): void
    {
        $interceptors = [new FakeDoubleInterceptor(), new FakeDoubleInterceptor()];
        $this->bind->bindInterceptors('getDouble', $interceptors);
        $this->assertSame($this->bind->getBindings()['getDouble'], $interceptors);
    }

    public function testBind(): void
    {
        $interceptors = [new FakeDoubleInterceptor()];
        $pointcut = new Pointcut((new Matcher())->startsWith('Ray'), (new Matcher())->startsWith('get'), $interceptors);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut]);
        $this->assertArrayHasKey('getDouble', $this->bind->getBindings());
        $this->assertSame($this->bind->getBindings()['getDouble'], $interceptors);
    }

    public function testBindWithConstructor(): void
    {
        $interceptors = [new FakeDoubleInterceptor()];
        $pointcut = new Pointcut((new Matcher())->startsWith('Ray'), (new Matcher())->startsWith('get'), $interceptors);
        $this->bind->bind(FakeConstructorClass::class, [$pointcut]);
        $this->assertArrayHasKey('getDouble', $this->bind->getBindings());
        $this->assertSame($this->bind->getBindings()['getDouble'], $interceptors);
    }

    public function testBindUnmatched(): void
    {
        $interceptors = [new FakeDoubleInterceptor()];
        $pointcut = new Pointcut((new Matcher())->startsWith('XXX'), (new Matcher())->startsWith('get'), $interceptors);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut]);
        $this->assertSame($this->bind->getBindings(), []);
    }

    public function testToString(): void
    {
        $nullBind = (string) (new Bind());
        $this->assertIsString($nullBind);

        $interceptors = [new FakeDoubleInterceptor()];
        $pointcut = new Pointcut((new Matcher())->startsWith('Ray'), (new Matcher())->startsWith('get'), $interceptors);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut]);
        $bindString = (string) $this->bind;
        $this->assertIsString($bindString);
        $this->assertIsString($nullBind, $bindString);
    }

    public function testMyMatcher(): void
    {
        $interceptors = [new FakeDoubleInterceptor()];
        $pointcut = new Pointcut(new FakeMatcher(), (new Matcher())->any(), $interceptors);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut]);
        $this->assertArrayHasKey('getDouble', $this->bind->getBindings());
        $this->assertSame($this->bind->getBindings()['getDouble'], $interceptors);
    }

    public function testNotClassMatch(): void
    {
        $pointcut = new Pointcut(new FakeMatcher(false), (new Matcher())->any(), [new FakeDoubleInterceptor()]);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut]);
        $this->assertArrayNotHasKey('getDouble', $this->bind->getBindings());
    }

    public function testOnionAnnotation(): void
    {
        $onion1 = new FakeOnionInterceptor1();
        $onion2 = new FakeOnionInterceptor2();
        $onion3 = new FakeOnionInterceptor3();
        $pointcut0 = new Pointcut((new Matcher())->any(), (new Matcher())->startsWith('XXX'), [$onion1]);
        $pointcut1 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker::class), [$onion1]);
        $pointcut2 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker2::class), [$onion2]);
        $pointcut3 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker3::class), [$onion3]);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut0, $pointcut1, $pointcut2, $pointcut3]);
        $actual = $this->bind->getBindings();
        $expect = [
            'getDouble' => [$onion3, $onion2, $onion1],
        ];
        $this->assertSame($expect, $actual);
    }

    public function testOnionAnnotationAndPriorityPointcut(): void
    {
        $onion1 = new FakeOnionInterceptor1();
        $onion2 = new FakeOnionInterceptor2();
        $onion3 = new FakeOnionInterceptor3();
        $onion4 = new FakeOnionInterceptor4();
        $pointcut0 = new Pointcut((new Matcher())->any(), (new Matcher())->startsWith('XXX'), [$onion1]);
        $pointcut1 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker::class), [$onion1]);
        $pointcut2 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker2::class), [$onion2]);
        $pointcut3 = new Pointcut((new Matcher())->any(), (new Matcher())->annotatedWith(FakeMarker3::class), [$onion3]);
        $pointcut4 = new PriorityPointcut((new Matcher())->annotatedWith(FakeResource::class), (new Matcher())->any(), [$onion4]);
        $this->bind->bind(FakeAnnotateClass::class, [$pointcut0, $pointcut1, $pointcut2, $pointcut3, $pointcut4]);
        $actual = $this->bind->getBindings();
        $expect = [
            'getDouble' => [$onion4, $onion3, $onion2, $onion1],
        ];
        $this->assertSame($expect, $actual);
    }
}
