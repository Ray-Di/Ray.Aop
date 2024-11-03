<?php

declare(strict_types=1);

namespace Ray\Aop;

/**
 * @psalm-import-type MethodInterceptors from Aspect
 * @psalm-import-type MethodBindings from Aspect
 * @psalm-import-type ClassBindings from Aspect
 * @psalm-import-type MatcherConfig from Aspect
 * @psalm-type AspectBind = array{
 *   bind: Bind,
 *   pointcut: array{
 *     method: string,
 *     interceptors: MethodInterceptors
 *   }
 * }
 * @psalm-type BindCode = array{
 *   interface: class-string,
 *   name: string|null,
 *   target: class-string|string|null,
 *   scope: string|null,
 *   bindWith: string,
 *   type: 'to'|'toProvider'|'toInstance'|'toConstructor'|'toNull'
 * }
 * @psalm-type AspectCode = array{
 *   target: class-string,
 *   methods: MethodBindings
 * }
 * @psalm-type CompileResult = array{
 *   binds: array<string, BindCode>,
 *   aspectBinds: array<string, AspectCode>
 * }
 * @psalm-type ConstructorArguments = list<mixed>
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
