<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;
use ReflectionMethod;

use function func_get_args;

/**
 * Abstract matcher base class
 *
 * @psalm-type MatcherArguments = array<array-key, mixed>
 * @psalm-type Arguments = array<array-key, mixed>
 */
abstract class AbstractMatcher
{
    /** @var array<array-key, mixed> */
    protected $arguments = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->arguments = func_get_args();
    }

    /**
     * Match class condition
     *
     * @param ReflectionClass<object> $class     Target class
     * @param MatcherArguments        $arguments Matching condition arguments
     *
     * @return bool
     */
    abstract public function matchesClass(ReflectionClass $class, array $arguments);

    /**
     * Match method condition
     *
     * @param ReflectionMethod $method    Target method
     * @param MatcherArguments $arguments Matching condition arguments
     *
     * @return bool
     */
    abstract public function matchesMethod(ReflectionMethod $method, array $arguments);

    /**
     * Return matching condition arguments
     *
     * @return Arguments
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }
}
