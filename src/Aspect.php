<?php

declare(strict_types=1);

namespace Ray\Aop;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;

use function array_slice;
use function basename;
use function count;
use function end;
use function extension_loaded;
use function get_declared_classes;
use function method_intercept;
use function strcasecmp;
use function sys_get_temp_dir;

final class Aspect
{
    /** @var string|null */
    private $tmpDir;

    /** @var array<array{classMatcher: AbstractMatcher, methodMatcher: AbstractMatcher, interceptors: array}> */
    private $matchers = [];

    /** @var array<string, array<string, array>> */
    private $bound = [];

    public function __construct(?string $tmpDir = null)
    {
        $this->tmpDir = $tmpDir ?? sys_get_temp_dir();
    }

    public function bind(AbstractMatcher $classMatcher, AbstractMatcher $methodMatcher, array $interceptors): void
    {
        $this->matchers[] = [
            'classMatcher' => $classMatcher,
            'methodMatcher' => $methodMatcher,
            'interceptors' => $interceptors,
        ];
    }

    public function weave(string $classDir): void
    {
        if (! extension_loaded('rayaop')) {
            throw new RuntimeException('Ray.Aop extension is not loaded. Cannot use weave() method.');
        }

        $this->scanAndCompile($classDir);
        $this->applyInterceptors();
    }

    private function scanAndCompile(string $classDir): void
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($classDir)
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
        $reflection = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            if (! $matcher['classMatcher']->matchesClass($reflection, $matcher['classMatcher']->getArguments())) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                    continue;
                }

                $this->bound[$className][$method->getName()] = $matcher['interceptors'];
            }
        }
    }

    private function applyInterceptors(): void
    {
        if (! extension_loaded('rayaop')) {
            throw new RuntimeException('Ray.Aop extension is not loaded');
        }

        $dispatcher = new PeclDispatcher($this->bound);
        foreach ($this->bound as $className => $methods) {
            foreach ($methods as $methodName => $interceptors) {
                method_intercept($className, $methodName, $dispatcher);
            }
        }
    }

    /**
     * @param class-string<T> $className
     * @param array<mixed>    $args
     *
     * @return T
     *
     * @template T of object
     */
    public function newInstance(string $className, array $args = []): object
    {
        $reflection = new ReflectionClass($className);

        if ($reflection->isFinal() && extension_loaded('rayaop')) {
            return $this->newInstanceWithPecl($className, $args);
        }

        return $this->newInstanceWithPhp($className, $args);
    }

    private function newInstanceWithPecl(string $className, array $args): object
    {
        $instance = new $className(...$args);
        $this->processClass($className);
        $this->applyInterceptors();

        return $instance;
    }

    private function newInstanceWithPhp(string $className, array $args): object
    {
        if ($this->tmpDir === null) {
            throw new RuntimeException('Temporary directory is not set. It is required for PHP-based AOP.');
        }

        $bind = $this->createBind($className);
        $weaver = new Weaver($bind, $this->tmpDir);

        return $weaver->newInstance($className, $args);
    }

    private function createBind(string $className): Bind
    {
        $bind = new Bind();
        $reflection = new ReflectionClass($className);

        foreach ($this->matchers as $matcher) {
            if (! $matcher['classMatcher']->matchesClass($reflection, $matcher['classMatcher']->getArguments())) {
                continue;
            }

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $matcher['methodMatcher']->matchesMethod($method, $matcher['methodMatcher']->getArguments())) {
                    continue;
                }

                $bind->bindInterceptors($method->getName(), $matcher['interceptors']);
            }
        }

        return $bind;
    }
}
