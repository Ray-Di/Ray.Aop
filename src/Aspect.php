<?php

declare(strict_types=1);

namespace Ray\Aop;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;

use function array_slice;
use function assert;
use function basename;
use function count;
use function end;
use function extension_loaded;
use function get_declared_classes;
use function method_intercept;
use function strcasecmp;

final class Aspect
{
    /** @var string */
    private $classDir;

    /** @var array<array{classMatcher: AbstractMatcher, methodMatcher: AbstractMatcher, interceptors: array}> */
    private $matchers = [];

    /** @var array<string, array<string, array>> */
    private $bound = [];

    public function __construct(string $classDir)
    {
        $this->classDir = $classDir;
    }

    public function bind(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
    {
        $this->matchers[] = [
            'classMatcher' => $classMatcher,
            'methodMatcher' => $methodMatcher,
            'interceptors' => $interceptors,
        ];
    }

    public function weave(): void
    {
        $this->scanAndCompile();
        $this->applyInterceptors();
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param array<mixed> $args
     * @return T
     */
    public function newInstance(string $className, array $args = []): object
    {
        $bind = new Bind();
        $class = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            if ($matcher['classMatcher']->matchesClass($class, $matcher['classMatcher']->getArguments())) {
                foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    if ($matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                        $bind->bindInterceptors($method->getName(), $matcher['interceptors']);
                    }
                }
            }
        }

        $weaver = new Weaver($bind, $this->classDir);
        return $weaver->newInstance($className, $args);
    }

    private function scanAndCompile(): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->classDir)
        );

        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = $this->getClassNameFromFile($file->getPathname());
            if (! $className) {
                continue;
            }

            $this->processClass($className);
        }
    }

    private function getClassNameFromFile(string $file): ?string
    {
        $declaredClasses = get_declared_classes();
        $previousCount = count($declaredClasses);

        require_once $file;

        $newClasses = array_slice(get_declared_classes(), $previousCount);

        foreach ($newClasses as $class) {
            if (strcasecmp(basename($file, '.php'), $class) === 0) {
                return $class;
            }
        }

        return $newClasses ? end($newClasses) : null;
    }

    private function processClass(string $className): void
    {
        $reflClass = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            $classMathcer = $matcher['classMatcher'];
            assert($classMathcer instanceof AbstractMatcher);
            if (! $classMathcer->matchesClass($reflClass, $classMathcer->getArguments())) {
                continue;
            }

            foreach ($reflClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $methodMatcher = $matcher['methodMatcher'];
                assert($methodMatcher instanceof AbstractMatcher);

                if (! $methodMatcher->matchesMethod($method, $methodMatcher->getArguments())) {
                    continue;
                }

                $this->bound[$className][$method->getName()] = $matcher['interceptors'];
            }
        }
    }

    private function applyInterceptors(): void
    {
        if (! extension_loaded('rayaop')) {
            throw new \RuntimeException('Ray.Aop extension is not loaded');
        }

        $dispacher = new PeclDispatcher($this->bound);
        foreach ($this->bound as $className => $methods) {
            foreach ($methods as $methodName => $interceptors) {
                method_intercept($className, $methodName, $dispacher);
            }
        }
    }
}
