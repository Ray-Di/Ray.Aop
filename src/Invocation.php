<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayObject;

/**
 * This interface represents an invocation in the program.
 *
 * An invocation is a joinpoint and can be intercepted by an interceptor.
 *
 * @see http://aopalliance.sourceforge.net/doc/org/aopalliance/intercept/Invocation.html
 * @template T of object
 * @extends Joinpoint<T>
 */
interface Invocation extends Joinpoint
{
    /**
     * Get the arguments as an array object.
     *
     * @return ArrayObject<int, mixed> the argument of the invocation ['arg1', 'arg2']
     */
    public function getArguments(): ArrayObject;

    /**
     * Get the named arguments as an array object.
     *
     * @return ArrayObject<non-empty-string, mixed> the argument of the invocation  [`paramName1'=>'arg1', `paramName2'=>'arg2']
     */
    public function getNamedArguments(): ArrayObject;
}
