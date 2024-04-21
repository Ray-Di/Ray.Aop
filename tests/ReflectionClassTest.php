<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;

use function count;

class ReflectionClassTest extends TestCase
{
    /** @var ReflectionClass<object> */
    private $class;

    public function setUp(): void
    {
        $this->class = new ReflectionClass(FakeClassTartget::class);
    }

    public function testGetAnnottaions(): void
    {
        $annotations = $this->class->getAnnotations();
        $this->assertSame(2, count($annotations));
    }

    public function testGetAnnottaion(): void
    {
        $annotation = $this->class->getAnnotation(FakeResource::class);
        $this->assertInstanceOf(FakeResource::class, $annotation);
    }

    public function testGetMethods(): void
    {
        $methods = $this->class->getMethods();
        $this->assertAllInstanceOfMethod($methods);
    }

    public function testConstructor(): void
    {
        $constructor = $this->class->getConstructor();
        $this->assertInstanceOf(ReflectionMethod::class, $constructor);
    }

    public function testConstructorNull(): void
    {
        $constructor = (new ReflectionClass(FakeAnnotateClass::class))->getConstructor();
        $this->assertNull($constructor);
    }

    /** @param array<ReflectionMethod> $array */
    private function assertAllInstanceOfMethod(array $array): void
    {
        foreach ($array as $item) {
            $this->assertInstanceOf(ReflectionMethod::class, $item);
        }
    }

    public function testGetParentClass(): void
    {
        $this->assertInstanceOf(ReflectionClass::class, (new ReflectionClass(FakeMockChild::class))->getParentClass());
    }
}
