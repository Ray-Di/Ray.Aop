<?php

declare(strict_types=1);

namespace Ray\Aop\Demo;

use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;
use RuntimeException;

use function getdate;

class WeekendBlocker implements MethodInterceptor
{
    /** {@inheritDoc} */
    public function invoke(MethodInvocation $invocation)
    {
        $today = getdate();
        if ($today['weekday'][0] === 'S') {
            throw new RuntimeException(
                $invocation->getMethod()->getName() . ' not allowed on weekends!'
            );
        }

        return $invocation->proceed();
    }
}
