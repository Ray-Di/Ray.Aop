<?php

declare(strict_types=1);

namespace Ray\Aop;

use LogicException;

use function get_class;

class PeclDispatcher implements InterceptHandlerInterface
{
    /** @var array<string, Interceptor> */
    public function __construct(private array $interceptors)
    {
    }

    /** @inheritDoc */
    public function intercept(object $object, string $method, array $params): mixed
    {
        if (! isset($this->interceptors[get_class($object)][$method])) {
            throw new LogicException('Interceptors not found');
        }

        $interceptors = $this->interceptors[get_class($object)][$method];
        $invocation = new ReflectiveMethodInvocation($object, $method, $params, $interceptors);

        return $invocation->proceed();
    }
}
