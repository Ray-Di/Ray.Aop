<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakeNonFinalClass
{
    public function myMethod(): string
    {
        return 'original';
    }
}
