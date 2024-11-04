<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;
use Ray\Aop\Annotation\FakeMarker;

use function serialize;
use function unserialize;

class AnnotatedMatcherTest extends TestCase
{
    public function testSerialize(): void
    {
        $matcher = new AnnotatedMatcher('annotatedWith', [FakeMarker::class]);
        $this->assertInstanceOf(AnnotatedMatcher::class, unserialize(serialize($matcher)));
    }
}
