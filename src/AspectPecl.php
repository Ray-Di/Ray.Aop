<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

use function array_keys;
use function assert;
use function class_exists;
use function extension_loaded;
use function function_exists;
use function method_intercept;

/**
 * @psalm-type MethodBoundInterceptors = array<non-empty-string, MethodInterceptors>
 * @psalm-type ClassBoundInterceptors = array<class-string, MethodBoundInterceptors>
 * @psalm-import-type MatcherConfigList from Aspect
 * @psalm-import-type MethodInterceptors from Aspect
 * @codeCoverageIgnore
 */
final class AspectPecl
{
    public function __construct()
    {
        if (! extension_loaded('rayaop')) {
            throw new RuntimeException('Ray.Aop extension is not loaded. Cannot use weave() method.'); // @codeCoverageIgnore
        }
    }

    /**
     * Weave aspects into classes in the specified directory
     *
     * @param non-empty-string  $classDir Target class directory
     * @param MatcherConfigList $mathcers List of matchers and interceptors
     *
     * @throws RuntimeException When Ray.Aop extension is not loaded.
     */
    public function weave(string $classDir, array $mathcers): void
    {
        foreach (new ClassList($classDir) as $className) {
            $boundInterceptors = $this->getBoundInterceptors($className, $mathcers);
            $this->apply($boundInterceptors);
        }
    }

    /**
     * Process class for interception
     *
     * @param class-string      $className
     * @param MatcherConfigList $matchers
     *
     * @return ClassBoundInterceptors
     */
    private function getBoundInterceptors(string $className, array $matchers): array
    {
        assert(class_exists($className), $className);
        $reflection = new ReflectionClass($className);

        $bound = [];
        foreach ($matchers as $matcher) {
            if (! $matcher['classMatcher']->matchesClass($reflection, $matcher['classMatcher']->getArguments())) {
                continue;
            }

            /** @var ReflectionMethod[] $methods */
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (! $matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                    continue;
                }

                $bound[$className][$method->getName()] = $matcher['interceptors'];
            }
        }

        return $bound;
    }

    /**
     * Apply interceptors to bound methods
     *
     * @param ClassBoundInterceptors $boundInterceptors
     */
    private function apply(array $boundInterceptors): void
    {
        $dispatcher = new PeclDispatcher($boundInterceptors);
        assert(function_exists('\method_intercept')); // PECL Ray.Aop extension

        foreach ($boundInterceptors as $className => $methods) {
            $methodNames = array_keys($methods);
            foreach ($methodNames as $methodName) {
                assert($dispatcher instanceof MethodInterceptorInterface);
                method_intercept($className, $methodName, $dispatcher);
            }
        }
    }
}
