<?php

declare(strict_types=1);

namespace Ray\Aop;

class FakeMyInterceptor implements MethodInterceptor
{
    public function invoke(MethodInvocation $invocation): string
    {
        // Pre-processing logic
        $result = $invocation->proceed();

        // Post-processing logic
        return 'intercepted ' . $result;
    }
}
