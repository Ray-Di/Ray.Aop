<?php

declare(strict_types=1);

namespace Ray\Aop;

use Generator;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function array_filter;
use function class_exists;
use function count;
use function file_get_contents;
use function implode;
use function is_array;
use function token_get_all;

use const TOKEN_PARSE;

/** @implements IteratorAggregate<class-string> */
final class ClassList implements IteratorAggregate
{
    public const T_STRING1 = 313;
    public const T_STRING2 = 316;
    public const T_NS_SEPARATOR1 = 379;
    public const T_NS_SEPARATOR2 = 382;

    /**
     * Return FQCN from PHP file
     */
    public static function from(string $file): ?string
    {
        $tokens = token_get_all((string) file_get_contents($file), TOKEN_PARSE);
        $count = count($tokens);
        $position = 0;
        $namespace = '';
        $collectingNamespace = false;
        $namespaceBuffer = [];

        while ($position < $count) {
            $token = $tokens[$position];
            if (! is_array($token)) {
                if ($collectingNamespace && $token === ';') {
                    $namespace = implode('\\', array_filter($namespaceBuffer, static function ($part) {
                        return $part !== '';
                    }));
                    $collectingNamespace = false;
                }

                $position++;
                continue;
            }

            switch ($token[1]) {
                case 'namespace':
                    $collectingNamespace = true;
                    $namespaceBuffer = [];
                    $position++;
                    continue 2;
                case 'class':
                    $className = $tokens[$position + 2][1];
                    $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;

                    return class_exists($fqcn) ? $fqcn : null;

                default:
                    if (
                        $collectingNamespace && $token[0] === self::T_STRING1
                        || $collectingNamespace && $token[0] === self::T_STRING2
                        || $token[0] === self::T_NS_SEPARATOR1
                        || $token[0] === self::T_NS_SEPARATOR2
                    ) {
                        $namespaceBuffer[] = $token[1];
                    }
            }

            $position++;
        }

        return null;
    }

    /** @var string */
    private $directory;

    public function __construct(string $directory)
    {
        $this->directory = $directory;
    }

    /** @return Generator<class-string> */
    public function getIterator(): Generator
    {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory)
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $className = self::from($file->getPathname());
            if ($className === null) {
                continue;
            }

            if (! class_exists($className)) {
                continue;
            }

            yield $className;
        }
    }
}
