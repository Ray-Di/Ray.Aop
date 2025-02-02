<?php

declare(strict_types=1);

namespace Ray\Aop\Annotation;

use Attribute;
use Ray\Aop\FakePhp81Enum;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FakeMarker5
{
    public function __construct(public readonly FakePhp81Enum $fruit)
    {
    }
}
