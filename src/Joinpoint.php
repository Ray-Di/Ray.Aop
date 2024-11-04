<?php

declare(strict_types=1);

namespace Ray\Aop;

/**
 * This interface represents a generic runtime joinpoint (in the AOP
 * terminology).
 *
 * <p>A runtime joinpoint is an <i>event</i> that occurs on a static
 * joinpoint (i.e. a location in a the program). For instance, an
 * invocation is the runtime joinpoint on a method (static joinpoint).
 * The static part of a given joinpoint can be generically retrieved
 * using the {@link #getStaticPart()} method.
 *
 * <p>In the context of an interception framework, a runtime joinpoint
 * is then the reification of an access to an accessible object (a
 * method, a constructor, a field), i.e. the static part of the
 * joinpoint. It is passed to the interceptors that are installed on
 * the static joinpoint.
 *
 * @see Interceptor
 * @template T of object
 */
interface Joinpoint
{
    /**
     * Proceeds to the next interceptor in the chain.
     *
     * <p>The implementation and the semantics of this method depends
     * on the actual joinpoint type (see the children interfaces).
     *
     * @return mixed see the children interfaces' proceed definition.
     *
     * Throwable if the joinpoint throws an exception
     */
    public function proceed();

    /**
     * Returns the object that holds the current joinpoint's static part.
     *
     * <p>For instance, the target object for an invocation.
     *
     * @return T (can be null if the accessible object is static)
     *
     * @psalm-mutation-free
     */
    public function getThis();
}
