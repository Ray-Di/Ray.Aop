<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Annotation\FakeMarker4;
use Ray\Aop\Annotation\FakeMarkerName;

class FakePhp8Types implements FakeNullInterface, \Ray\Aop\FakeNullInterface1
{
    // A method with no type declaration
    public function method1($param1) {}

    // Methods with scalar type declarations
    public function method2(string $param1) {}
    public function method3(int $param1) {}

    // Method with nullable type declarations
    public function method4(?string $param1) {}
    public function method5(?int $param1) {}

    // Method with default values
    public function method6(string $param1 = "default") {}
    public function method7(?int $param1 = null) {}

    // Method with reference parameter
    public function method8(&$param1) {}

    // Method with array type hint
    public function method9(array $param1) {}
    public function method10(?array $param1) {}

    // Variadic method
    public function method11(...$params) {}

    // Methods with union types
    public function method12(int|string $param1) {}
    public function method13(\DateTime|string $param1) {}

    // Methods with nullable union types
    public function method14(int|string|null $param1) {}
    public function method15(\DateTime|string|null $param1) {}

    // Methods with return types
    public function method16(): string { return ""; }
    public function method17(): \DateTime { return new \DateTime(); }

    // Method with union return type
    public function method18(): int|string { return "string"; }

    // Method with nullable union return type
    public function method19(): null|int|string { return null; }
    public function method20(): \DateTime|string|null { return null; }

    /**
     * PHPDoc
     */
    #[FakeMarker4([1, 2], 3)]
    public function method21() {}

    #[FakeMarkerName(a: 1, b: 'string', c:true)]
    public function method22() {}

}
