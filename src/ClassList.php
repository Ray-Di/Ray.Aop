<?php

declare(strict_types=1);

namespace Ray\Aop;

use Generator;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function class_exists;
use function count;
use function file_get_contents;
use function is_array;
use function token_get_all;

use const TOKEN_PARSE;

/** @implements IteratorAggregate<class-string> */
final class ClassList implements IteratorAggregate
{
    /**
     * Return FQCN from PHP file
     */
    public static function from(string $file): ?string
    {
        $tokens = token_get_all(file_get_contents($file), TOKEN_PARSE);
        $count = count($tokens);
        $position = 0;
        $namespace = '';

        while ($position < $count) {
            $token = $tokens[$position];
            if (! is_array($token)) {
                $position++;
                continue;
            }

            switch ($token[1]) {
                case 'namespace':
                    $namespace = $tokens[$position + 2][1];
                    $position += 2;
                    continue 2;
                case 'class':
                    $className = $tokens[$position + 2][1];
                    $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;

                    return class_exists($fqcn) ? $fqcn : null;
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

            yield $className;
        }
    }
}
