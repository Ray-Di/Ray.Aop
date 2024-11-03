<?php

declare(strict_types=1);

namespace Ray\Aop\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
abstract class AbstractAssisted
{
    /**
     * Add null default to listed parameters
     *
     * @var string[]
     * @readonly
     */
    public $values;
}
