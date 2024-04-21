<?php

declare(strict_types=1);

namespace Ray\Aop;

use Doctrine\Common\Annotations\AnnotationException;

use function array_key_exists;
use function array_merge;
use function serialize;

final class Bind implements BindInterface
{
    /** @var array<string, array<MethodInterceptor>> */
    private $bindings = [];

    /** @var MethodMatch */
    private $methodMatch;

    /** @throws AnnotationException */
    public function __construct()
    {
        $this->methodMatch = new MethodMatch($this);
    }

    /** @return list<string> */
    public function __sleep(): array
    {
        return ['bindings'];
    }

    /**
     * {@inheritDoc}
     */
    public function bind(string $class, array $pointcuts): BindInterface
    {
        $pointcuts = $this->getAnnotationPointcuts($pointcuts);
        $reflectionClass = new ReflectionClass($class);
        $methods = $reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC);
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
     * {@inheritDoc}
     */
    public function bindInterceptors(string $method, array $interceptors): BindInterface
    {
        $this->bindings[$method] = ! array_key_exists($method, $this->bindings) ? $interceptors : array_merge(
            $this->bindings[$method],
            $interceptors
        );

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * {@inheritDoc}
     */
    public function __toString(): string
    {
        return serialize($this->bindings);
    }

    /**
     * @param Pointcut[] $pointcuts
     *
     * @return Pointcut[]
     */
    public function getAnnotationPointcuts(array &$pointcuts): array
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
