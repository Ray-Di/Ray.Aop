<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayObject;
use ReflectionClass;
use ReflectionObject;

use function array_shift;
use function assert;
use function call_user_func_array;
use function class_exists;
use function is_callable;

final class ReflectiveMethodInvocation implements MethodInvocation
{
    /** @var object */
    private $object;

    /** @var ArrayObject<int, mixed> */
    private $arguments;

    /** @var string */
    private $method;

    /** @var MethodInterceptor[] */
    private $interceptors;

    /** @var callable */
    private $callable;

    /**
     * @param array<MethodInterceptor> $interceptors
     * @param array<int, mixed>        $arguments
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

    /**
     * {@inheritDoc}
     */
    public function getMethod(): ReflectionMethod
    {
        if ($this->object instanceof WeavedInterface) {
            $class = (new ReflectionObject($this->object))->getParentClass();
            assert($class instanceof ReflectionClass);
            assert(class_exists($class->name));
            $method = new ReflectionMethod($class->name, $this->method);
            $method->setObject($this->object);

            return $method;
        }

        return new ReflectionMethod($this->object, $this->method);
    }

    /**
     * {@inheritDoc}
     */
    public function getArguments(): ArrayObject
    {
        return $this->arguments;
    }

    /**
     * {@inheritDoc}
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
     */
    public function getThis()
    {
        return $this->object;
    }
}
