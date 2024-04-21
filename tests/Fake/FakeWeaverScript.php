<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakeWeaverScript implements FakeNullInterface, FakeNullInterface1
{
    public function returnSame($a)
    {
        return $a;
    }
}
