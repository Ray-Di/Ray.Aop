<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\Aop\Exception\LogicException;

/**
 * @psalm-import-type ClassBoundInterceptors from AspectPecl
 * @psalm-type Interceptors = array<MethodInterceptor>
 */
class PeclDispatcher implements MethodInterceptorInterface
{
    /** @param ClassBoundInterceptors $interceptors */
    public function __construct(private array $interceptors)
    {
    }

    /**
     * @inheritDoc
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-suppress TypeDoesNotContainType
     * @psalm-suppress MixedArgumentTypeCoercion
     *
     * (Psalm seems to have a problem with the signature of this method.)
     */
    public function intercept(object $object, string $method, array $params): mixed
    {
        $class = get_class($object);
        if (! isset($this->interceptors[$class][$method])) {
            throw new LogicException('Interceptors not found');
        }

        /** @var Interceptors $interceptors */
        $interceptors = $this->interceptors[$class][$method];

        $invocation = new ReflectiveMethodInvocation($object, $method, $params, $interceptors);

        return $invocation->proceed();
    }
}
