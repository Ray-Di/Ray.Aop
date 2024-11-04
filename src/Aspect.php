<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

use function sys_get_temp_dir;

/**
 * Aspect class manages aspect weaving and method interception
 *
 * @psalm-type MethodInterceptors = array<array-key, MethodInterceptor>
 * @psalm-type MethodBindings = array<string, MethodInterceptors>
 * @psalm-type ClassBindings = array<class-string, MethodBindings>
 * @psalm-type MatcherConfig = array{
 *   classMatcher: AbstractMatcher,
 *   methodMatcher: AbstractMatcher,
 *   interceptors: MethodInterceptors
 * }
 * @psalm-type MatcherConfigList = array<array-key, MatcherConfig>
 * @psalm-type Arguments = array<array-key, mixed>
 */
final class Aspect
{
    /**
     * Temporary directory for generated proxy classes
     *
     * @var non-empty-string
     * @readonly
     */
    private $tmpDir;

    /**
     * Collection of matcher configurations
     *
     * @var MatcherConfigList
     */
    private $matchers = [];

    /** @param non-empty-string|null $tmpDir Directory for generated proxy classes */
    public function __construct(?string $tmpDir = null)
    {
        if ($tmpDir === null) {
            $tmp = sys_get_temp_dir();
            $this->tmpDir = $tmp !== '' ? $tmp : '/tmp';

            return;
        }

        $this->tmpDir = $tmpDir;
    }

    /**
     * Bind interceptors to matched methods
     *
     * @param AbstractMatcher    $classMatcher  Class matcher
     * @param AbstractMatcher    $methodMatcher Method matcher
     * @param MethodInterceptors $interceptors  List of interceptors
     */
    public function bind(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
    {
        $matcherConfig = [
            'classMatcher' => $classMatcher,
            'methodMatcher' => $methodMatcher,
            'interceptors' => $interceptors,
        ];

        $this->matchers[] = $matcherConfig;
    }

    /**
     * Weave aspects into classes in the specified directory
     *
     * @param non-empty-string $classDir Target class directory
     *
     * @throws RuntimeException When Ray.Aop extension is not loaded.
     */
    public function weave(string $classDir): void
    {
        (new AspectPecl())->weave($classDir, $this->matchers);
    }

    /**
     * Create new instance with woven aspects
     *
     * @param class-string<T> $className Target class name
     * @param list<mixed>     $args      Constructor arguments
     *
     * @return T New instance with aspects
     *
     * @throws RuntimeException When temporary directory is not set for PHP-based AOP.
     *
     * @template T of object
     */
    public function newInstance(string $className, array $args = []): object
    {
        $bind = $this->createBind($className);
        $weaver = new Weaver($bind, $this->tmpDir);

        /** @psalm-var T */
        return $weaver->newInstance($className, $args);
    }

    /**
     * Create bind instance for the given class
     *
     * @param class-string $className
     */
    private function createBind(string $className): Bind
    {
        $bind = new Bind();
        $reflection = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            if (! $matcher['classMatcher']->matchesClass($reflection, $matcher['classMatcher']->getArguments())) {
                continue;
            }

            /** @var ReflectionMethod[] $methods */
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                if (! $matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                    continue; // @codeCoverageIgnore
                }

                $bind->bindInterceptors($method->getName(), $matcher['interceptors']);
            }
        }

        return $bind;
    }
}
