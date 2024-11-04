<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakeMyClass
{
    public function myMethod(): string
    {
        return 'original';
    }
}
