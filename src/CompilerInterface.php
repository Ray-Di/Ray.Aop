<?php

declare(strict_types=1);

namespace Ray\Aop;

/**
 * @psalm-import-type MethodInterceptors from Types
 * @psalm-import-type MethodBindings from Types
 * @psalm-import-type ClassBindings from Types
 * @psalm-import-type MatcherConfig from Types
 * @psalm-import-type ConstructorArguments from Types
 */
interface CompilerInterface
{
    /**
     * Compile dependency bindings and aspect bindings
     *
     *  Return class code which implements class-string<$class> with generated class name "{$class}_Generated_{$randomString}"
     *
     * @param class-string<T> $class Target class name
     * @param BindInterface   $bind  Dependency binding
     *
     * @return class-string<T> Generated class name with interceptor weaved code
     *
     * @template T of object
     */
    public function compile(string $class, BindInterface $bind): string;

    /**
     * Return new instance weaved interceptor(s)
     *
     * @param class-string<T>      $class Target class name
     * @param ConstructorArguments $args  Constructor arguments
     * @param BindInterface        $bind  Dependency binding
     *
     * @return T New instance with woven interceptors
     *
     * @template T of object
     */
    public function newInstance(string $class, array $args, BindInterface $bind);
}
