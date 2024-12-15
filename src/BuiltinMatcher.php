<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Exception\InvalidMatcherException;
use ReflectionMethod;

use function assert;
use function class_exists;
use function ucwords;

/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-import-type MatcherArguments from Types
 */
class BuiltinMatcher extends AbstractMatcher
{
    /**
     * @var string
     * @readonly
     */
    private $matcherName;

    /**
     * @var AbstractMatcher
     * @readonly
     */
    private $matcher;

    /** @param MatcherArguments $arguments */
    public function __construct(string $matcherName, array $arguments)
    {
        parent::__construct();

        $this->matcherName = $matcherName;
        $this->arguments = $arguments;
        $matcherClass = 'Ray\Aop\Matcher\\' . ucwords($this->matcherName) . 'Matcher';
        assert(class_exists($matcherClass));
        $matcher = (new ReflectionClass($matcherClass))->newInstance();
        if (! $matcher instanceof AbstractMatcher) {
            throw new InvalidMatcherException($matcherClass);
        }

        $this->matcher = $matcher;
    }

    /**
     * {@inheritDoc}
     */
    public function matchesClass(\ReflectionClass $class, array $arguments): bool
    {
        return $this->matcher->matchesClass($class, $arguments);
    }

    /**
     * {@inheritDoc}
     */
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        return $this->matcher->matchesMethod($method, $arguments);
    }
}
