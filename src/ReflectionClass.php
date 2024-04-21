<?php

declare(strict_types=1);

namespace Ray\Aop;

use Ray\ServiceLocator\ServiceLocator;
use ReturnTypeWillChange;

use function get_class_methods;

/**
 * @template T of object
 * @template-extends \ReflectionClass<T>
 */
class ReflectionClass extends \ReflectionClass implements Reader
{
    /**
     * {@inheritDoc}
     *
     * @psalm-suppress NoInterfaceProperties
     */
    public function getAnnotations(): array
    {
        /** @var list<object> $annotations */
        $annotations = ServiceLocator::getReader()->getClassAnnotations(new \ReflectionClass($this->name));

        return $annotations;
    }

    /**
     * {@inheritDoc}
     */
    public function getAnnotation(string $annotationName)
    {
        $annotations = $this->getAnnotations();
        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @param int|null $filter
     *
     * @return list<ReflectionMethod>
     *
     * @psalm-external-mutation-free
     */
    public function getMethods($filter = null): array
    {
        unset($filter);
        $methods = [];
        $methodNames = get_class_methods($this->name);
        foreach ($methodNames as $methodName) {
            $methods[] = new ReflectionMethod($this->name, $methodName);
        }

        return $methods;
    }

    /**
     * @psalm-suppress MethodSignatureMismatch
     * @psalm-external-mutation-free
     */
    public function getConstructor(): ?ReflectionMethod
    {
        $parent = parent::getConstructor();
        if ($parent === null) {
            return null;
        }

        return new ReflectionMethod($parent->class, $parent->name);
    }

    /**
     * @return ReflectionClass<object>|false
     *
     * @psalm-external-mutation-free
     */
    #[ReturnTypeWillChange]
    public function getParentClass()
    {
        $parent = \ReflectionClass::getParentClass();

        return $parent instanceof \ReflectionClass ? (new ReflectionClass($parent->getName())) : false;
    }
}
