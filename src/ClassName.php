<?php

declare(strict_types=1);

namespace Ray\Aop;

use function count;
use function file_exists;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function token_get_all;

use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_NAMESPACE;
use const T_STRING;
use const T_WHITESPACE;
use const TOKEN_PARSE;

/**
 * Extract fully qualified class name from file
 *
 * @psalm-type TokenValue = array{0: int, 1: string, 2: int}
 * @psalm-type Token = TokenValue|string
 * @psalm-type Tokens = array<int, Token>
 * @psalm-type ParserResult = array{string, int}
 */
final class ClassName
{
    private const T_NAME_QUALIFIED = 316;

    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        /** @var Tokens $tokens */
        $tokens = token_get_all(file_get_contents($filePath), TOKEN_PARSE); // @phpstan-ignore-line
        $count = count($tokens);
        $position = 0;
        $namespace = '';

        while ($position < $count) {
            $token = $tokens[$position];
            if (! is_array($token)) {
                $position++;
                continue;
            }

            switch ($token[0]) {
                case T_NAMESPACE:
                    $namespaceResult = self::parseNamespace($tokens, $position + 1, $count);
                    /** @var string */
                    $namespace = $namespaceResult[0];
                    $position = $namespaceResult[1];
                    continue 2;
                case T_CLASS:
                    $className = self::parseClassName($tokens, $position + 1, $count);
                    if ($className !== null) {
                        return $namespace !== '' ? $namespace . '\\' . $className : $className;
                    }
            }

            $position++;
        }

        return null;
    }

    /**
     * Parse namespace from tokens
     *
     * @param Tokens $tokens
     *
     * @return ParserResult Array containing [namespace, new position]
     */
    private static function parseNamespace(array $tokens, int $position, int $count): array
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if ($position >= $count) {
            return ['', $position];
        }

        $token = $tokens[$position];
        if (! is_array($token)) {
            return ['', $position];
        }

        // Qualified namespace token (PHP 8.0+)
        if ($token[0] === self::T_NAME_QUALIFIED) {
            return [$token[1], $position + 1];
        }

        // Legacy namespace parsing
        $namespaceParts = [];
        while ($position < $count) {
            $token = $tokens[$position];

            if (! is_array($token)) {
                if ($token === ';' || $token === '{') {
                    break;
                }

                if ($token === '\\') {
                    $namespaceParts[] = '\\';
                }

                $position++;
                continue;
            }

            if ($token[0] === T_STRING) {
                $namespaceParts[] = $token[1];
            }

            $position++;
        }

        return [implode('', $namespaceParts), $position];
    }

    /**
     * Parse class name from tokens
     *
     * @param Tokens $tokens
     */
    private static function parseClassName(array $tokens, int $position, int $count): ?string
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if ($position >= $count) {
            return null;
        }

        $token = $tokens[$position];
        if (! is_array($token)) {
            return null;
        }

        return $token[1];
    }

    /**
     * Skip whitespace and comments
     *
     * @param Tokens $tokens
     */
    private static function skipWhitespace(array $tokens, int $position, int $count): int
    {
        while (
            $position < $count &&
            is_array($tokens[$position]) &&
            in_array($tokens[$position][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
        ) {
            $position++;
        }

        return $position;
    }
}
