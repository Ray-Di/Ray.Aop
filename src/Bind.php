<?php

declare(strict_types=1);

namespace Ray\Aop;

use function array_key_exists;
use function array_merge;
use function serialize;

/**
 * Bind class manages method interception bindings
 *
 * @psalm-import-type MethodInterceptors from Types
 * @psalm-import-type MethodBindings from Types
 * @psalm-import-type Pointcuts from Types
 * @psalm-import-type MethodName from Types
 */
final class Bind implements BindInterface
{
    /**
     * Method interceptor bindings
     *
     * @var MethodBindings
     */
    private $bindings = [];

    /**
     * @var MethodMatch
     * @readonly
     */
    private $methodMatch;

    public function __construct()
    {
        $this->methodMatch = new MethodMatch($this);
    }

    /** @return list<'bindings'> */
    public function __sleep(): array
    {
        return ['bindings'];
    }

    /**
     * Bind pointcuts to methods
     *
     * @param class-string $class     Target class
     * @param Pointcuts    $pointcuts List of pointcuts
     */
    public function bind(string $class, array $pointcuts): BindInterface
    {
        $pointcuts = $this->getAnnotationPointcuts($pointcuts);
        $reflectionClass = new ReflectionClass($class);

        $methods = $reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->getName() === '__construct') {
                continue;
            }

            $rayMethod = new ReflectionMethod($reflectionClass->getName(), $method->getName());
            ($this->methodMatch)($reflectionClass, $rayMethod, $pointcuts);
        }

        return $this;
    }

    /**
     * Bind interceptors to a method
     *
     * @param MethodName         $method       Method name
     * @param MethodInterceptors $interceptors List of interceptors
     */
    public function bindInterceptors(string $method, array $interceptors): BindInterface
    {
        $this->bindings[$method] = ! array_key_exists($method, $this->bindings)
            ? $interceptors
            : array_merge($this->bindings[$method], $interceptors);

        return $this;
    }

    /**
     * Get all method bindings
     *
     * @return MethodBindings
     *
     * @psalm-mutation-free
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Get serialized representation of bindings
     */
    public function __toString(): string
    {
        return serialize($this->bindings);
    }

    /**
     * @param Pointcut[] $pointcuts
     *
     * @return Pointcuts
     */
    private function getAnnotationPointcuts(array $pointcuts): array
    {
        $keyPointcuts = [];
        foreach ($pointcuts as $key => $pointcut) {
            if ($pointcut->methodMatcher instanceof AnnotatedMatcher) {
                $key = $pointcut->methodMatcher->annotation;
            }

            $keyPointcuts[$key] = $pointcut;
        }

        return $keyPointcuts;
    }
}
