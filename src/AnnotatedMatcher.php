<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;
use ReflectionMethod;

/**
 * Matcher for annotations
 */
class AnnotatedMatcher extends BuiltinMatcher
{
    /**
     * @var class-string
     * @readonly
     */
    public $annotation;

    /**
     * @param non-empty-string       $matcherName
     * @param array{0: class-string} $arguments   Single element array containing annotation class name
     */
    public function __construct(string $matcherName, array $arguments)
    {
        parent::__construct($matcherName, $arguments);

        $this->annotation = $arguments[0];
    }

    /**
     * {@inheritDoc}
     */
    public function matchesClass(ReflectionClass $class, array $arguments): bool
    {
        $rayClass = $class instanceof \Ray\Aop\ReflectionClass ? $class : new \Ray\Aop\ReflectionClass($class->getName());
        /** @var class-string $annotationName */
        $annotationName = $arguments[0];
        $annotation = $rayClass->getAnnotation($annotationName);

        return $annotation !== null;
    }

    /**
     * {@inheritDoc}
     */
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        $rayMethod = $method instanceof \Ray\Aop\ReflectionMethod ? $method : new \Ray\Aop\ReflectionMethod($method->class, $method->getName());
        /** @var class-string $annotationName */
        $annotationName = $arguments[0];
        $annotation = $rayMethod->getAnnotation($annotationName);

        return $annotation !== null;
    }
}
