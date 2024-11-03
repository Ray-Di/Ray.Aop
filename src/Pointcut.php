<?php

declare(strict_types=1);

namespace Ray\Aop;

class Pointcut
{
    /**
     * @var AbstractMatcher
     * @readonly
     */
    public $classMatcher;

    /**
     * @var AbstractMatcher
     * @readonly
     */
    public $methodMatcher;

    /**
     * @var array<MethodInterceptor|class-string<MethodInterceptor>>
     * @readonly
     */
    public $interceptors = [];

    /** @param array<MethodInterceptor|class-string<MethodInterceptor>> $interceptors */
    public function __construct(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors)
    {
        $this->classMatcher = $classMatcher;
        $this->methodMatcher = $methodMatcher;
        $this->interceptors = $interceptors;
    }
}
