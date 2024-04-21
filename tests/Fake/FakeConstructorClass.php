<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakeConstructorClass
{
    public function __construct()
    {
    }

    public function getDouble($a)
    {
        return $a * 2;
    }
}
