<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;

use function crc32;
use function filemtime;

/**
 * Fully qualified name including postfix
 *
 * @psalm-immutable
 */
final class AopPostfixClassName
{
    /**
     * @var string
     * @readonly
     */
    public $fqn;

    /**
     * @var string
     * @readonly
     */
    public $postFix;

    /** @param class-string $class */
    public function __construct(string $class, string $bindings, string $classDir)
    {
        $fileTime = (string) filemtime((string) (new ReflectionClass($class))->getFileName());
        $this->postFix = '_' . crc32($fileTime . $bindings . $classDir);
        $this->fqn = $class . $this->postFix;
    }
}
