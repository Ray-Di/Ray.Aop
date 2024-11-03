<?php

declare(strict_types=1);

namespace Ray\Aop;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use SplFileInfo;

use function array_keys;
use function array_slice;
use function assert;
use function basename;
use function class_exists;
use function count;
use function end;
use function extension_loaded;
use function get_declared_classes;
use function method_intercept; // @phpstan-ignore-line
use function strcasecmp;

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
 * @psalm-type Arguments = array<array-key, mixed>
 */
final class PeclAspect
{
    /**
     * Collection of matcher configurations
     *
     * @var array<array-key, MatcherConfig>
     */
    private $matchers = [];

    /**
     * Bound interceptors map
     *
     * @var ClassBindings
     */
    private $bound = [];

    public function __construct(array $matchers, array $bound)
    {
        $this->matchers = $matchers;
        $this->bound = $bound;
    }

    /**
     * Weave aspects into classes in the specified directory
     *
     * @param string $classDir Target class directory
     *
     * @throws RuntimeException When Ray.Aop extension is not loaded.
     */
    public function weave(string $classDir): void
    {
        if (! extension_loaded('rayaop')) {
            throw new RuntimeException('Ray.Aop extension is not loaded. Cannot use weave() method.');
        }

        $this->scanDirectory($classDir);
        $this->applyInterceptors();
    }

    /**
     * Create instance using PECL extension
     *
     * @param class-string<T> $className
     * @param Arguments       $args
     *
     * @return T
     *
     * @template T of object
     */
    public function newInstance(string $className, array $args): object
    {
        /** @psalm-suppress MixedMethodCall */
        $instance = new $className(...$args);
        $this->processClass($className);
        $this->applyInterceptors();

        /** @var T $instance */
        return $instance;
    }

    /**
     * Scan directory and compile classes
     */
    private function scanDirectory(string $classDir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($classDir)
        );

        /** @var SplFileInfo[] $files */
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getPathname());
            if ($className === null) {
                continue;
            }

            $this->processClass($className);
        }
    }

    /**
     * Get class name from file
     *
     * @return class-string|null
     */
    private function getClassNameFromFile(string $file): ?string
    {
        $declaredClasses = get_declared_classes();
        $previousCount = count($declaredClasses);

        /** @psalm-suppress UnresolvableInclude */
        require_once $file;

        $newClasses = array_slice(get_declared_classes(), $previousCount);

        foreach ($newClasses as $class) {
            if (strcasecmp(basename($file, '.php'), $class) === 0) {
                return $class;
            }
        }

        return $newClasses ? end($newClasses) : null;
    }

    /**
     * Process class for interception
     *
     * @param class-string $className
     */
    private function processClass(string $className): void
    {
        assert(class_exists($className));
        $reflection = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            if (! $matcher['classMatcher']->matchesClass($reflection, $matcher['classMatcher']->getArguments())) {
                continue;
            }

            /** @var ReflectionMethod[] $methods */
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (! $matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                    continue;
                }

                $this->bound[$className][$method->getName()] = $matcher['interceptors'];
            }
        }
    }

    /**
     * Apply interceptors to bound methods
     */
    private function applyInterceptors(): void
    {
        if (! extension_loaded('rayaop')) {
            throw new RuntimeException('Ray.Aop extension is not loaded');
        }

        $dispatcher = new PeclDispatcher($this->bound);

        foreach ($this->bound as $className => $methods) {
            $methodNames = array_keys($methods);
            foreach ($methodNames as $methodName) {
                assert($dispatcher instanceof MethodInterceptorInterface);
                /** @psalm-suppress UndefinedFunction */
                method_intercept($className, $methodName, $dispatcher);  // @phpstan-ignore-line
            }
        }
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
                    continue;
                }

                $bind->bindInterceptors($method->getName(), $matcher['interceptors']);
            }
        }

        return $bind;
    }
}
