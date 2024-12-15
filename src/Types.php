<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayObject;

/**
 * Type definitions for Ray.Aop
 *
 * @phpcs:disable SlevomatCodingStandard.Commenting.DocCommentSpacing
 * @template T of object
 *
 * Domain Types
 * @psalm-type ScriptDir = non-empty-string
 * @psalm-type ClassName = class-string
 * @psalm-type MethodName = non-empty-string
 * @psalm-type AspectClassName = non-empty-string
 * @psalm-type BindingName = non-empty-string
 * @psalm-type MatcherName = non-empty-string
 *
 * Base Types
 * @psalm-type ArgumentList = ArrayObject<int, mixed>
 * @psalm-type NamedArguments = ArrayObject<MethodName, mixed>
 * @psalm-type InterceptorList = array<MethodInterceptor>
 * @psalm-type ConstructorArguments = list<mixed>
 * @psalm-type ReflectionClassTemplate = ReflectionClass<T>
 *
 * Matcher Types
 * @psalm-type MatcherArguments = array<array-key, mixed>
 * @psalm-type MethodInterceptors = array<array-key, MethodInterceptor>
 * @psalm-type MatcherConfig = array{
 *   classMatcher: AbstractMatcher,
 *   methodMatcher: AbstractMatcher,
 *   interceptors: MethodInterceptors
 * }
 * @psalm-type Arguments = array<array-key, mixed>
 * @psalm-type BuiltinMethodsNames = list<non-empty-string>
 *
 * Method and Binding Types
 * @psalm-type MethodBindings = array<MethodName, MethodInterceptors>
 * @psalm-type ClassBindings = array<ClassName, MethodBindings>
 * @psalm-type MatcherConfigList = array<array-key, MatcherConfig>
 * @psalm-type MethodBoundInterceptors = array<MethodName, MethodInterceptors>
 * @psalm-type ClassBoundInterceptors = array<ClassName, MethodBoundInterceptors>
 *
 * PointCut Types
 * @psalm-type Pointcuts = array<Pointcut>
 * @phpcs:enable
 */
final class Types
{
    /** @codeCoverageIgnore */
    private function __construct()
    {
    }
}
