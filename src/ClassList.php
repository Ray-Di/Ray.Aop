<?php

declare(strict_types=1);

namespace Ray\Aop;

use Generator;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use SplFileInfo;

use function class_exists;
use function file_get_contents;
use function preg_match;
use function preg_replace;
use function str_replace;
use function strpos;
use function strstr;
use function trim;

/** @implements IteratorAggregate<class-string> */
final class ClassList implements IteratorAggregate
{
    private const MULTI_LINE_COMMENT_PATTERN = '/\/\*.*?\*\//s';
    private const SINGLE_LINE_COMMENT_PATTERN = '/\/\/.*$/m';
    private const STRING_LITERAL_PATTERN = '/([\'"])((?:\\\1|.)*?)\1/s';
    private const NAMESPACE_PATTERN = '/namespace\s+([a-zA-Z0-9\\\\_ ]+?);/s';
    private const CLASS_NAME_PATTERN = '/class\s+([a-zA-Z0-9_]+)(?:\s+extends|\s+implements|\s*{)/s';

    /**
     * Extracts the Fully Qualified Class Name (FQCN) from a PHP file.
     */
    public static function getClassName(string $file): ?string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return null; // @codeCoverageIgnore
        }

        if (strpos($content, '<?php') !== false) {
            $content = strstr($content, '<?php');
        }

        // Remove comments
        $content = preg_replace(self::MULTI_LINE_COMMENT_PATTERN, '', (string) $content); // Multi-line comments
        $content = preg_replace(self::SINGLE_LINE_COMMENT_PATTERN, '', (string) $content); // Single-line comments

        // Remove string literals
        $content = preg_replace(self::STRING_LITERAL_PATTERN, '', (string) $content);

        // Extract namespace
        $namespace = '';
        if (preg_match(self::NAMESPACE_PATTERN, (string) $content, $matches)) {
            $namespace = trim(str_replace(["\n", "\r", ' '], '', $matches[1]));
        }

        // Extract class name
        if (preg_match(self::CLASS_NAME_PATTERN, (string) $content, $matches)) {
            $className = $matches[1];
            $fqcn = $namespace !== '' ? $namespace . '\\' . $className : $className;

            return class_exists($fqcn) ? $fqcn : null;
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
        $files = new RegexIterator(
            new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->directory)),
            '/\.php$/'
        );

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $className = self::getClassName($file->getPathname());
            if ($className === null || ! class_exists($className)) {
                continue;
            }

            yield $className;
        }
    }
}
