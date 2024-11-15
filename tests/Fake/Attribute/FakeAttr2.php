<?php

namespace Ray\Aop\Attribute;

final class FakeAttr2
{
    private function __construct(
        private string $name,
        private int $age
    ){
    }
}
