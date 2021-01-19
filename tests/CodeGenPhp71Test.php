<?php

declare(strict_types=1);

namespace Ray\Aop;

use PhpParser\BuilderFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class CodeGenPhp71Test extends TestCase
{
    /** @var CodeGen */
    private $codeGen;

    protected function setUp(): void
    {
        $parser = (new ParserFactory())->newInstance();
        $factory = new BuilderFactory();
        $aopClassName = new AopClassName(__DIR__ . '/tmp');
        $this->codeGen = new CodeGen(
            $factory,
            new VisitorFactory($parser),
            new AopClass($parser, $factory, $aopClassName)
        );
    }

    public function testReturnTypeVoid(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('returnTypeVoid', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $expected = 'function returnTypeVoid() : void';
        $this->assertStringContainsString($expected, $code->code);
    }

    public function testReturnTypeVoidContainsNoReturnValue(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('returnTypeVoid', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $this->assertStringNotContainsString('return ', $code->code);
        $this->assertStringContainsString('return;', $code->code);
    }

    public function testReturnTypeNullable(): Code
    {
        $bind = new Bind();
        $bind->bindInterceptors('returnNullable', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $expected = 'function returnNullable(string $str) : ?';
        $this->assertStringContainsString($expected, $code->code);

        return $code;
    }

    /**
     * @depends testReturnTypeNullable
     */
    public function testContainsStatement(Code $code): void
    {
        $this->assertStringContainsString("declare (strict_types=1);\n", $code->code);
        $this->assertStringContainsString("use Composer\\Autoload;\n", $code->code);
    }

    public function testNullableParam(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('nullableParam', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $expected = 'function nullableParam(?int $id, ?string $name = null)';
        $this->assertStringContainsString($expected, $code->code);
    }

    public function testTypedParam(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('typed', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $expected = 'public function typed(SplObjectStorage $storage)';
        $this->assertStringContainsString($expected, $code->code);
    }

    public function testUseTyped(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('useTyped', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp71NullableClass::class), $bind);
        $expected = 'public function useTyped(CodeGen $codeGen)';
        $this->assertStringContainsString($expected, $code->code);
    }
}
