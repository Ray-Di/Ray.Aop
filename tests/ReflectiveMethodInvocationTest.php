<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Annotation\FakeClassMarker;
use Ray\Aop\Annotation\FakeMarker;
use ReflectionMethod;

class ReflectiveMethodInvocationTest extends TestCase
{
    /** @var ReflectiveMethodInvocation<FakeClass> */
    protected $invocation;

    /** @var FakeClass */
    protected $fake;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fake = new FakeClass();
        $this->invocation = new ReflectiveMethodInvocation($this->fake, 'add', [1]);
    }

    public function testGetMethod(): void
    {
        $methodReflection = $this->invocation->getMethod();
        $this->assertInstanceOf(ReflectionMethod::class, $methodReflection);
    }

    public function testGetMethodMethodName(): void
    {
        $methodReflection = $this->invocation->getMethod();
        $this->assertSame(FakeClass::class, $methodReflection->class);
        $this->assertSame('add', $methodReflection->name);
    }

    public function testGetArguments(): void
    {
        $args = $this->invocation->getArguments();
        $this->assertSame((array) $args, [1]);
    }

    public function testProceed(): void
    {
        $this->invocation->proceed();
        $this->assertSame(1, $this->fake->a);
    }

    public function testProceedTwoTimes(): void
    {
        $this->invocation->proceed();
        $this->invocation->proceed();
        $this->assertSame(2, $this->fake->a);
    }

    public function testGetThis(): void
    {
        $actual = $this->invocation->getThis();
        $this->assertSame($this->fake, $actual);
    }

    public function testGetParentMethod(): void
    {
        $fake = new FakeWeavedClass();
        $invocation = new ReflectiveMethodInvocation($fake, 'add', [1]);
        $method = $invocation->getMethod();
        $this->assertSame(FakeClass::class, $method->class);
        $this->assertSame('add', $method->name);
    }

    public function testProceedMultipleInterceptors(): void
    {
        $fake = new FakeWeavedClass();
        $invocation = new ReflectiveMethodInvocation($fake, 'add', [1], [new FakeInterceptor(), new FakeInterceptor()]);
        $invocation->proceed();
        $this->assertSame(1, $fake->a);
    }

    public function testGetNamedArguments(): void
    {
        $args = $this->invocation->getNamedArguments();
        $this->assertSame((array) $args, ['n' => 1]);
    }

    public function testGetNamedArgumentsWithDefaultValue(): void
    {
        $invocation = new ReflectiveMethodInvocation(new FakeWeavedClass(), 'defaultValue', [1, null], [new FakeInterceptor(), new FakeInterceptor()]);
        $args = $invocation->getNamedArguments();
        $this->assertSame((array) $args, ['a' => 1, 'b' => null]);
    }

    public function testGetAnnotation(): void
    {
        $fakeMarker = $this->invocation->getMethod()->getAnnotation(FakeMarker::class);
        $this->assertInstanceOf(FakeMarker::class, $fakeMarker);
    }

    public function testGetClassAnnotation(): void
    {
        $fakeMarker = $this->invocation->getMethod()->getDeclaringClass()->getAnnotation(FakeClassMarker::class);
        $this->assertInstanceOf(FakeClassMarker::class, $fakeMarker);
    }
}
