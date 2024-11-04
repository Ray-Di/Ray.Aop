<?php

declare(strict_types=1);

namespace Ray\Aop;

/**
 * Intercepts calls on an interface on its way to the target. These are nested "on top" of the target.
 *
 * The user should implement the invoke(MethodInvocation) method to modify the original behavior.
 * E.g. the following class implements a tracing interceptor (traces all the calls on the intercepted method(s)):
 *
 * @see http://aopalliance.sourceforge.net/doc/org/aopalliance/intercept/MethodInterceptor.html
 */
interface MethodInterceptor extends Interceptor
{
    /**
     * Implement this method to perform extra treatments before and after the invocation.
     *
     * Polite implementations would certainly like to invoke {@link Joinpoint#proceed()}.
     *
     * @param MethodInvocation<T> $invocation the method invocation joinpoint
     *
     * @return mixed the result of the call to {@link *               Joinpoint#proceed()}, might be intercepted by the
     *               interceptor
     *
     * @template T of object
     */
    public function invoke(MethodInvocation $invocation);
}
