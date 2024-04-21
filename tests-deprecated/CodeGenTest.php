<?php

declare(strict_types=1);


use PhpParser\BuilderFactory;
use PHPUnit\Framework\TestCase;
use Ray\Aop\AopClass;
use Ray\Aop\AopClassName;
use Ray\Aop\Bind;
use Ray\Aop\CodeGen;
use Ray\Aop\Exception\InvalidSourceClassException;
use Ray\Aop\FakePhp7Class;
use Ray\Aop\FakePhp7ReturnTypeClass;
use Ray\Aop\ParserFactory;
use Ray\Aop\VisitorFactory;

class CodeGenTest extends TestCase
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

    public function testTypeDeclarations(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('run', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp7Class::class), $bind);
        $expected = 'function run(string $a, int $b, float $c, bool $d) : array';
        $this->assertStringContainsString($expected, $code->code);
    }

    public function testReturnType(): void
    {
        $bind = new Bind();
        $bind->bindInterceptors('returnTypeArray', []);
        $code = $this->codeGen->generate(new ReflectionClass(FakePhp7ReturnTypeClass::class), $bind);
        $expected = 'function returnTypeArray() : array';
        $this->assertStringContainsString($expected, $code->code);
    }

    public function testInvalidSourceClass(): void
    {
        $this->expectException(InvalidSourceClassException::class);
        $this->codeGen->generate(new ReflectionClass(stdClass::class), new Bind());
    }
}
