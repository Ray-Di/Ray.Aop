<?php

declare(strict_types=1);

namespace Ray\Aop\Aspect\Fake\src;

class FakeMyClass
{
    public function myMethod(): string
    {
        return 'original';
    }
}
