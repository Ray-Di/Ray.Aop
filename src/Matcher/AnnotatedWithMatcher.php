<?php

declare(strict_types=1);

namespace Ray\Aop\Matcher;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Ray\Aop\AbstractMatcher;
use ReflectionClass;
use ReflectionMethod;

final class AnnotatedWithMatcher extends AbstractMatcher
{
    /** @var AnnotationReader */
    private $reader;

    /**
     * @throws AnnotationException
     */
    public function __construct()
    {
        parent::__construct();
        $this->reader = new AnnotationReader();
    }

    /**
     * {@inheritdoc}
     */
    public function matchesClass(ReflectionClass $class, array $arguments): bool
    {
        /** @var array<class-string> $arguments */
        [$annotation] = $arguments;
        $annotation = $this->reader->getClassAnnotation($class, $annotation);

        return (bool) $annotation;
    }

    /**
     * {@inheritdoc}
     */
    public function matchesMethod(ReflectionMethod $method, array $arguments): bool
    {
        /** @var array<class-string> $arguments */
        [$annotation] = $arguments;
        $annotation = $this->reader->getMethodAnnotation($method, $annotation);

        return (bool) $annotation;
    }
}
