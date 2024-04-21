<?php

declare(strict_types=1);

namespace Ray\Aop\Annotation;

use Attribute;

/**
 * @Annotation
 * @Target("METHOD")
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FakeMarker4
{
    /** @var array  */
    private $a;
    /** @var int */
    private $b;
    public function __construct(array $a, int $b)
    {
        $this->a = $a;
        $this->b = $b;
    }
}
