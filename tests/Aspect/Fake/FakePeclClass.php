<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakePeclClass
{
    public function myMethod(): string
    {
        return 'original';
    }
}
