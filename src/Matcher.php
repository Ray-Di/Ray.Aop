<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Exception\InvalidAnnotationException;
use Ray\Aop\Exception\InvalidArgumentException;

use function class_exists;

class Matcher implements MatcherInterface
{
    /**
     * {@inheritDoc}
     *
     * @psalm-mutation-free
     */
    public function any()
    {
        return new BuiltinMatcher(__FUNCTION__, []);
    }

    /**
     * {@inheritDoc}
     */
    public function annotatedWith($annotationName): AbstractMatcher
    {
        if (! class_exists($annotationName)) {
            throw new InvalidAnnotationException($annotationName);
        }

        return new AnnotatedMatcher(__FUNCTION__, [$annotationName]);
    }

    /**
     * {@inheritDoc}
     */
    public function subclassesOf($superClass): AbstractMatcher
    {
        if (! class_exists($superClass)) {
            throw new InvalidArgumentException($superClass);
        }

        return new BuiltinMatcher(__FUNCTION__, [$superClass]);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-mutation-free
     */
    public function startsWith($prefix): AbstractMatcher
    {
        return new BuiltinMatcher(__FUNCTION__, [$prefix]);
    }

    // @codingStandardsIgnoreStart

    /**
     * {@inheritdoc}
     */
    public function logicalOr(AbstractMatcher $matcherA, AbstractMatcher $matcherB) : AbstractMatcher
    {
        return new BuiltinMatcher(__FUNCTION__, func_get_args());
    }

    /**
     * {@inheritdoc}
     */
    public function logicalAnd(AbstractMatcher $matcherA, AbstractMatcher $matcherB) : AbstractMatcher
    {
        return new BuiltinMatcher(__FUNCTION__, func_get_args());
    }

    // @codingStandardsIgnoreEnd

    /**
     * {@inheritDoc}
     */
    public function logicalNot(AbstractMatcher $matcher): AbstractMatcher
    {
        return new BuiltinMatcher(__FUNCTION__, [$matcher]);
    }
}
