<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Annotation\FakeMarker5;

class FakePhp81Types
{
    #[FakeMarker5(FakePhp81Enum::Apple)]
    public function method200() {}
}
