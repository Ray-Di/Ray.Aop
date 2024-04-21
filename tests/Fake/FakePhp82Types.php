<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Annotation\FakeMarker4;
use Ray\Aop\Annotation\FakeMarkerName;

class FakePhp82Types
{
    public function method100(): false { return false; }
    public function method101(): true { return true; }
    public function method102(): null { return null; }
    public function method103(): FakeNullInterface&FakeNullInterface1 { return $this;}
    public function method104(): FakeNullInterface|FakeNullInterface1 { return $this;}
    public function method105(): FakeNullInterface|string { return $this;}
    public function method106(): (FakeNullInterface&FakeNullInterface1)|string { return $this;}
}
