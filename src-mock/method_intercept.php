<?php

namespace Ray\Aop;

if (! function_exists('method_intercept')) {
    /**
     * @return mixed
     */
    function method_intercept(string $class, string $method, MethodInterceptorInterface $interceptor)
    {
        unset($class, $method, $interceptor);
    }
}
