<?php

declare(strict_types=1);

namespace Ray\Aop\Matcher;

use ArrayObject;
use Ray\Aop\AbstractMatcher;
use Ray\Aop\Types;
use ReflectionClass;
use ReflectionMethod;

use function in_array;
use function strpos;

/**
 * @psalm-import-type Arguments from Types
 * @psalm-import-type BuiltinMethodsNames from Types
 */
final class AnyMatcher extends AbstractMatcher
{
    /**
     * @var BuiltinMethodsNames
     * @readonly
     */
    private static $builtinMethods = [];

    public function __construct()
    {
        parent::__construct();

        if (self::$builtinMethods !== []) {
            return;
        }

        $this->setBuildInMethods();
    }

    /**
     * {@inheritDoc}
     */
    public function matchesClass(ReflectionClass $class, array $arguments): bool
    {
        unset($class, $arguments);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        unset($arguments);

        return ! ($this->isMagicMethod($method->name) || $this->isBuiltinMethod($method->name));
    }

    private function setBuildInMethods(): void
    {
        $methods = (new ReflectionClass(ArrayObject::class))->getMethods();
        foreach ($methods as $method) {
            self::$builtinMethods[] = $method->name;
        }
    }

    /** @psalm-pure */
    private function isMagicMethod(string $name): bool
    {
        return strpos($name, '__') === 0;
    }

    /** @psalm-external-mutation-free */
    private function isBuiltinMethod(string $name): bool
    {
        return in_array($name, self::$builtinMethods, true);
    }
}
