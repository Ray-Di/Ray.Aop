<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayObject;
use ReflectionClass;
use ReflectionObject;

use function array_shift;
use function assert;
use function call_user_func_array;
use function is_callable;

/**
 * @psalm-import-type ArgumentList from Types
 * @psalm-import-type NamedArguments from Types
 * @psalm-import-type InterceptorList from Types
 * @template T of object
 * @implements MethodInvocation<T>
 */
final class ReflectiveMethodInvocation implements MethodInvocation
{
    /**
     * @var T
     * @readonly
     */
    private $object;

    /**
     * @var ArgumentList
     * @readonly
     */
    private $arguments;

    /**
     * @var non-empty-string
     * @readonly
     */
    private $method;

    /**
     * @var InterceptorList
     * @psalm-readonly-allow-private-mutation
     */
    private $interceptors;

    /**
     * @var callable(mixed...): mixed
     * @readonly
     */
    private $callable;

    /**
     * @param T                 $object       Target object
     * @param non-empty-string  $method       Method name
     * @param array<int, mixed> $arguments    Method arguments
     * @param InterceptorList   $interceptors Method interceptors
     */
    public function __construct(
        object $object,
        string $method,
        array $arguments,
        array $interceptors = []
    ) {
        $this->object = $object;
        $this->method = $method;
        $callable = [$object, $method];
        assert(is_callable($callable));
        $this->callable = $callable;
        $this->arguments = new ArrayObject($arguments);
        $this->interceptors = $interceptors;
    }

    public function getMethod(): ReflectionMethod
    {
        if ($this->object instanceof WeavedInterface) {
            $class = (new ReflectionObject($this->object))->getParentClass();
            assert($class instanceof ReflectionClass);
            $method = new ReflectionMethod($class->name, $this->method);
            $method->setObject($this->object);

            return $method;
        }

        return new ReflectionMethod($this->object, $this->method);
    }

    /**
     * {@inheritDoc}
     *
     * @return ArgumentList
     *
     * @psalm-mutation-free
     */
    public function getArguments(): ArrayObject
    {
        return $this->arguments;
    }

    /**
     * {@inheritDoc}
     *
     * @return NamedArguments
     */
    public function getNamedArguments(): ArrayObject
    {
        $args = $this->getArguments();
        $params = $this->getMethod()->getParameters();
        $namedParams = [];
        foreach ($params as $param) {
            $pos = $param->getPosition();
            $name = $param->getName();
            /** @psalm-suppress MixedAssignment */
            $namedParams[$name] = $args[$pos];
        }

        return new ArrayObject($namedParams); // @phpstan-ignore-line
    }

    /**
     * {@inheritDoc}
     */
    public function proceed()
    {
        $interceptor = array_shift($this->interceptors);
        if ($interceptor instanceof MethodInterceptor) {
            return $interceptor->invoke($this);
        }

        return call_user_func_array($this->callable, (array) $this->arguments);
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-external-mutation-free
     */
    public function getThis()
    {
        return $this->object;
    }
}
