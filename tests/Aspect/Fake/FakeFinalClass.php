<?php

declare(strict_types=1);

namespace Ray\Aop;

final class FakeFinalClass
{
    public function myMethod(): string
    {
        return 'original';
    }
}
