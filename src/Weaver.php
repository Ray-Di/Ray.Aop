<?php

declare(strict_types=1);

namespace Ray\Aop;

use function assert;
use function class_exists;
use function file_exists;
use function sprintf;
use function str_replace;

final class Weaver
{
    /** @var BindInterface */
    private $bind;

    /** @var string */
    private $bindName;

    /** @var string */
    private $classDir;

    /** @var Compiler */
    private $compiler;

    public function __construct(BindInterface $bind, string $classDir)
    {
        $this->bind = $bind;
        $this->bindName = (string) $bind;
        $this->compiler = new Compiler($classDir);
        $this->classDir = $classDir;
    }

    /**
     * @param class-string<T> $class
     * @param list<mixed>     $args
     *
     * @return T
     *
     * @template T of object
     */
    public function newInstance(string $class, array $args): object
    {
        $aopClass = $this->weave($class);
        $instance = (new ReflectionClass($aopClass))->newInstanceArgs($args);
        assert(isset($instance->bindings));
        $instance->bindings = $this->bind->getBindings();
        assert($instance instanceof $class);

        return $instance;
    }

    /**
     * @param class-string $class
     *
     * @return class-string
     */
    public function weave(string $class): string
    {
        $aopClass = new AopPostfixClassName($class, $this->bindName);
        if (class_exists($aopClass->fqn, false)) {
            return $aopClass->fqn;
        }

        if ($this->loadClass($aopClass->fqn)) {
            assert(class_exists($aopClass->fqn));

            return $aopClass->fqn;
        }

        $this->compiler->compile($class, $this->bind);
        assert(class_exists($aopClass->fqn));

        return $aopClass->fqn;
    }

    private function loadClass(string $class): bool
    {
        $file = sprintf('%s/%s.php', $this->classDir, str_replace('\\', '_', $class));
        if (file_exists($file)) {
            require $file;

            return true;
        }

        return false;
    }
}
