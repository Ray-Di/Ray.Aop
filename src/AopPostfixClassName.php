<?php

declare(strict_types=1);

namespace Ray\Aop;

use ReflectionClass;

use function crc32;
use function filemtime;

final class AopPostfixClassName
{
    /** @var string  */
    public $fqn;

    /** @var string  */
    public $postFix;

    /** @param class-string $class */
    public function __construct(string $class, string $bindings)
    {
        $fileTime = (string) filemtime((string) (new ReflectionClass($class))->getFileName());
        $this->postFix = '_' . crc32($fileTime . $bindings);
        $this->fqn = $class . $this->postFix;
    }
}
