<?php

declare(strict_types=1);

namespace Ray\Aop;

use Generator;
use IteratorAggregate;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function class_exists;
use function file_get_contents;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_replace;
use function strstr;
use function trim;

/** @implements IteratorAggregate<class-string> */
final class ClassList implements IteratorAggregate
{
    /**
     * Return FQCN from PHP file
     */
    public static function from(string $file): ?string
    {
        $content = (string) file_get_contents($file);

        // PHPタグの外のコンテンツを除去
        if (str_contains($content, '<?php')) {
            $content = strstr($content, '<?php');
        }

        $content = (string) $content;
        // コメントを除去
        $content = preg_replace('/\/\*.*?\*\//s', '', $content); // 複数行コメント
        $content = preg_replace('/\/\/.*$/m', '', (string) $content);     // 単行コメント

        // 文字列リテラルを除去
        $content = preg_replace('/([\'"])((?:\\\1|.)*?)\1/s', '', (string) $content);

        // namespace取得
        $namespace = '';
        if (preg_match('/namespace\s+([a-zA-Z0-9\\\\_ ]+?);/s', (string) $content, $matches)) {
            $namespace = trim(str_replace(['\n', '\r', ' '], '', $matches[1]));
        }

        // クラス名取得
        if (preg_match('/class\s+([a-zA-Z0-9_]+)(?:\s+extends|\s+implements|\s*{)/s', (string) $content, $matches)) {
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
