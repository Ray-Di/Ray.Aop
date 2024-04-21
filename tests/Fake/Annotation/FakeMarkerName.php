<?php

declare(strict_types=1);

namespace Ray\Aop\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation\NamedArgumentConstructor;

/**
 * @Annotation
 * @Target("METHOD")
 * @NamedArgumentConstructor
 */
#[Attribute(Attribute::TARGET_METHOD)]
final class FakeMarkerName
{
    /** @var int */
    public $a;

    /** @var string */
    public $b;

    /** @var bool */
    public $c;

    public function __construct(int $a, string $b, bool $c)
    {
        $this->a = $a;
        $this->b = $b;
        $this->c = $c;
    }
}
