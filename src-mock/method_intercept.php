<?php

if (! function_exists('method_intercept')) {
    /**
     * @return mixed
     */
    function method_intercept(string $class, string $method, array $params, object $object)
    {
        $interceptor = new $class();
        return $interceptor->intercept($object, $method, $params);
    }
}
