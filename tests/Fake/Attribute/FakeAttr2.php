<?php

namespace Ray\Aop\Attribute;

use Attribute;

#[Attribute]
final class FakeAttr2
{
    private function __construct(
        private string $name,
        private int $age
    ){
    }
}
