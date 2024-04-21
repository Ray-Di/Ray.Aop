<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayIterator;
use Doctrine\Common\Annotations\AnnotationReader;
use FakeGlobalEmptyNamespaced;
use FakeGlobalNamespaced;
use LogicException;
use PHPUnit\Framework\TestCase;
use Ray\Aop\Annotation\FakeMarker;
use Ray\Aop\Annotation\FakeMarker3;
use Ray\Aop\Exception\NotWritableException;
use ReflectionClass;
use ReflectionMethod;

use function array_shift;
use function assert;
use function class_exists;
use function file_get_contents;
use function passthru;
use function property_exists;
use function serialize;
use function unserialize;

class CompilerTest extends TestCase
{
    /** @var BindInterface */
    private $bind;

    /** @var Compiler */
    private $compiler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->compiler = new Compiler(__DIR__ . '/tmp');
        $matcher = new Matcher();
        $pointcut = new Pointcut($matcher->any(), $matcher->startsWith('return'), [new FakeDoubleInterceptor()]);
        $this->bind = (new Bind())->bind(FakeWeaved::class, [$pointcut]);
    }

    public function testNewInstance(): FakeMock
    {
        $mock = $this->compiler->newInstance(FakeMock::class, [], $this->bind);
        $this->assertInstanceOf(FakeMock::class, $mock);
        assert($mock instanceof FakeMock);

        return $mock;
    }

    public function testNewInstanceTwice(): void
    {
        $class1 = $this->compiler->compile(FakeMock::class, $this->bind);
        $class2 = $this->compiler->compile(FakeMock::class, $this->bind);
        $this->assertTrue(class_exists($class1));
        $this->assertSame($class1, $class2);
        assert(class_exists($class1));
        $class1File = (new ReflectionClass($class1))->getFileName();
        $class2File = (new ReflectionClass($class1))->getFileName();
        $this->assertSame($class1File, $class2File);
    }

    /** @depends testNewInstance */
    public function testParentClassName(object $class): void
    {
        $parent = (new ReflectionClass($class))->getParentClass();
        if (! ($parent instanceof ReflectionClass)) {
            return;
        }

        $this->assertSame(FakeMock::class, $parent->getName());
    }

    /** @depends testNewInstance */
    public function testBuildClassWeaved(FakeMock $weaved): void
    {
        assert(isset($weaved->bindings));
        $weaved->bindings = $this->bind->getBindings();
        $result = $weaved->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testParenteClass(): FakeMock
    {
        $weaved = $this->testNewInstance();
        $parent = (new ReflectionClass($weaved))->getParentClass();
        if ($parent instanceof ReflectionClass) {
            $this->assertSame(FakeMock::class, $parent->getName());
        }

        return $weaved;
    }

    /** @depends testNewInstance */
    public function testWeavedInterceptorWorks(FakeMock $weaved): void
    {
        $result = $weaved->returnSame(1);
        $this->assertSame(2, $result);
        $result = $weaved->returnSame(1);
        $this->assertSame(2, $result);
    }

    /** @depends testNewInstance */
    public function testMethodReturnValue(FakeMock $weaved): void
    {
        $num = new FakeNum();
        $num->value = 1;
        $result = $weaved->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testParentMethodIntercept(): void
    {
        $mock = $this->compiler->newInstance(FakeMockGrandChild::class, [], $this->bind);
        assert($mock instanceof FakeMockGrandChild);
        assert(property_exists($mock, 'bindings'));
        $mock->bindings = $this->bind->getBindings();
        $result = $mock->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testTypedParentMethodIntercept(): void
    {
        $bind = (new Bind())->bindInterceptors('passIterator', [new NullInterceptor()]);
        $mock = $this->compiler->newInstance(FakeTypedMockGrandChild::class, [], $bind);
        assert($mock instanceof FakeTypedMockGrandChild);
        assert(property_exists($mock, 'bindings'));
        $mock->bindings = $bind->getBindings();
        $result = $mock->passIterator(new ArrayIterator());
        $this->assertInstanceOf(ArrayIterator::class, $result);
    }

    public function testParentOfParentMethodIntercept(): void
    {
        $mock = $this->compiler->newInstance(FakeMockChildChild::class, [], $this->bind);
        assert($mock instanceof FakeMockChild);
        assert(property_exists($mock, 'bindings'));
        $mock->bindings = $this->bind->getBindings();
        $result = $mock->returnSame(1);
        $this->assertSame(2, $result);
    }

    public function testGetPrivateVal(): void
    {
        $mock = $this->compiler->newInstance(FakeMock::class, [], $this->bind);
        assert($mock instanceof FakeMock);
        $val = $mock->getPrivateVal();
        $this->assertSame($val, 1);
    }

    public function testCallAbortProceedInterceptorTwice(): void
    {
        $matcher = new Matcher();
        $pointcut = new Pointcut($matcher->any(), $matcher->startsWith('return'), [new FakeAbortProceedInterceptor()]);
        $this->bind->bind(FakeWeaved::class, [$pointcut]);
        $mock = $this->compiler->newInstance(FakeMock::class, [], $this->bind);
        assert($mock instanceof FakeMock);
        $this->assertSame(40, $mock->returnSame(1));
        $this->assertSame(40, $mock->returnSame(1));
    }

    public function testClassDocComment(): void
    {
        $weaved = $this->compiler->newInstance(FakeMock::class, [], $this->bind);
        /** @var FakeMock $weaved */
        $docComment = (string) (new ReflectionClass($weaved))->getDocComment();
        $expected = (new ReflectionClass(FakeMock::class))->getDocComment();
        $this->assertStringContainsString('/**', $docComment);
        $this->assertSame($expected, $docComment);
    }

    public function testMethodDocComment(): void
    {
        $weaved = $this->compiler->newInstance(FakeMock::class, [], $this->bind);
        /** @var FakeMock $weaved */
        $docComment = (string) (new ReflectionClass($weaved))->getMethods()[0]->getDocComment();
        $expected = (new ReflectionClass(FakeMock::class))->getMethods()[0]->getDocComment();

        $this->assertStringContainsString('/**', $docComment);
        $this->assertSame($expected, $docComment);
    }

    public function testNoDocComment(): void
    {
        $weaved = $this->compiler->newInstance(FakeMockNoDoc::class, [], $this->bind);
        /** @var FakeMock $weaved */
        $classDocComment = (new ReflectionClass($weaved))->getDocComment();
        $methodDocComment = (new ReflectionClass($weaved))->getMethods()[0]->getDocComment();

        $this->assertFalse((bool) $classDocComment);
        $this->assertFalse((bool) $methodDocComment);
    }

    public function testSerialize(): void
    {
        $compiler = unserialize(serialize($this->compiler));
        assert($compiler instanceof Compiler);
        $class = $compiler->compile(FakeMock::class, $this->bind);
        $this->assertTrue(class_exists($class));
    }

    public function testIncludeCompilerFile(): void
    {
        // new aop class file saved.
        passthru('php ' . __DIR__ . '/script/compile.php');
        // include class file.
        $class = require __DIR__ . '/script/compile.php';
        $isWeaved = (new ReflectionClass($class))->implementsInterface(WeavedInterface::class);
        $this->assertTrue($isWeaved);
    }

    public function testCompileNoBInd(): void
    {
        $class = $this->compiler->compile(FakeMock::class, new Bind());
        $this->assertSame(FakeMock::class, $class);
    }

    public function testAnnotation(): void
    {
        $class = $this->compiler->compile(FakeAnnotateClass::class, $this->bind);
        $annotations = (new AnnotationReader())->getMethodAnnotations(new ReflectionMethod($class, 'getDouble'));
        $this->assertCount(4, $annotations);
    }

    public function testNoNamespace(): void
    {
        $class = $this->compiler->compile(FakeAnnotateClassNoName::class, $this->bind);
        $annotations = (new AnnotationReader())->getMethodAnnotations(new ReflectionMethod($class, 'getDouble'));
        $this->assertCount(3, $annotations);
    }

    public function testArrayTypehintedAndCallable(): void
    {
        $class = $this->compiler->compile(FakeArrayTypehinted::class, $this->bind);
        assert(class_exists($class));
        $file = (string) file_get_contents((string) (new ReflectionClass($class))->getFileName());
        $expected = 'public function returnSame(array $arrayParam, callable $callableParam)';
        $this->assertStringContainsString($expected, $file);
    }

    public function testNotWritable(): void
    {
        $this->expectException(NotWritableException::class);

        new Compiler('./not_available');
    }

    public function testHasBound(): void
    {
        $this->compiler = new Compiler(__DIR__ . '/tmp');
        $this->bind = new Bind();
        $matcher = new Matcher();
        $pointcut = new Pointcut($matcher->any(), $matcher->startsWith('return'), [new FakeDoubleInterceptor()]);
        $this->bind->bind(FakeMock::class, [$pointcut]);
        $class = $this->compiler->compile(FakeMock::class, $this->bind);
        $this->assertTrue(class_exists($class));
    }

    public function testMethodAnnotationReader(): void
    {
        $bind = (new Bind())->bindInterceptors('getDouble', [new FakeMethodAnnotationReaderInterceptor()]);
        $compiler = new Compiler(__DIR__ . '/tmp');
        $mock = $compiler->newInstance(FakeAnnotateClass::class, [], $bind);
        assert($mock instanceof FakeAnnotateClass);
        $mock->getDouble(1);
        $methodAnnotation = FakeMethodAnnotationReaderInterceptor::$methodAnnotation;
        $this->assertInstanceOf(FakeMarker::class, $methodAnnotation);
        $methodAnnotations = FakeMethodAnnotationReaderInterceptor::$methodAnnotations;
        $this->assertCount(4, $methodAnnotations);
        $annotation = array_shift($methodAnnotations);
        $this->assertInstanceOf(FakeMarker3::class, $annotation);
    }

    /** @depends testMethodAnnotationReader */
    public function testClassAnnotationReader(): void
    {
        $classAnnotation = FakeMethodAnnotationReaderInterceptor::$classAnnotation;
        $classAnnotations = FakeMethodAnnotationReaderInterceptor::$classAnnotations;
        $this->assertInstanceOf(FakeClassAnnotation::class, $classAnnotation);
        $this->assertCount(2, $classAnnotations);
        $annotation = array_shift($classAnnotations);
        $this->assertInstanceOf(FakeResource::class, $annotation);
    }

    public function testMethodAnnotationReaderReturnNull(): void
    {
        $bind = (new Bind())->bindInterceptors('returnSame', [new FakeMethodAnnotationReaderInterceptor()]);
        $compiler = new Compiler(__DIR__ . '/tmp');
        $mock = $compiler->newInstance(FakeMock::class, [], $bind);
        if (! $mock instanceof FakeMock) {
            throw new LogicException();
        }

        $mock->returnSame(1);
        $this->assertNull(FakeMethodAnnotationReaderInterceptor::$methodAnnotation);
        $this->assertCount(0, FakeMethodAnnotationReaderInterceptor::$methodAnnotations);
    }

    public function testInterceptorCanChangeArgument(): void
    {
        $bind = (new Bind())->bindInterceptors('returnSame', [new FakeChangeArgsInterceptor()]);
        $compiler = new Compiler(__DIR__ . '/tmp');
        $mock = $compiler->newInstance(FakeMock::class, [], $bind);
        assert($mock instanceof FakeMock);
        $mock->returnSame(1);
        $this->assertSame('changed', $mock->returnSame(1));
    }

    public function testUnnamespacedClass(): void
    {
        $mock = $this->compiler->newInstance(FakeGlobalNamespaced::class, [], $this->bind);
        assert($mock instanceof FakeGlobalNamespaced);
        $this->assertInstanceOf(FakeGlobalNamespaced::class, $mock);
        $this->assertSame(2, $mock->returnSame(1));
    }

    public function testEmptyNamespaceClass(): void
    {
        $mock = $this->compiler->newInstance(FakeGlobalEmptyNamespaced::class, [], $this->bind);
        assert($mock instanceof FakeGlobalEmptyNamespaced);
        $this->assertInstanceOf(FakeGlobalEmptyNamespaced::class, $mock);
        $this->assertSame(2, $mock->returnSame(1));
    }

    public function testVoidFunction(): void
    {
        $bind = (new Bind())->bindInterceptors('returnTypeVoid', [new FakeChangeArgsInterceptor()]);
        $compiler = new Compiler(__DIR__ . '/tmp');
        $mock = $compiler->newInstance(FakePhp71NullableClass::class, [], $bind);
        assert($mock instanceof FakePhp71NullableClass);
        $mock->returnTypeVoid();
        $this->assertTrue($mock->returnTypeVoidCalled);
    }

    public function testNewInstanceWithAnonymousClass(): void
    {
        $mock = $this->compiler->newInstance(FakeAnonymousClass::class, [], $this->bind);
        $this->assertInstanceOf(FakeAnonymousClass::class, $mock);
        $this->assertInstanceOf(WeavedInterface::class, $mock);
    }
}
