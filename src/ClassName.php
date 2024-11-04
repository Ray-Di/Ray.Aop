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

use const T_ABSTRACT;
use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_FINAL;
use const T_NAMESPACE;
use const T_STRING;
use const T_WHITESPACE;

/**
 * Extracting the fully qualified class name from a given PHP file
 *
 * @psalm-type TokenValue = array{0: int, 1: string, 2: int}
 * @psalm-type Token = TokenValue|string
 * @psalm-type Tokens = array<int, Token>
 * @psalm-type ParserResult = array{string, int}
 * @psalm-immutable
 */
final class ClassName
{
    private const T_NAME_QUALIFIED = 316;

    /**
     * Extract fully qualified class name from file
     */
    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        /** @var Tokens $tokens */
        $tokens = token_get_all(file_get_contents($filePath)); // @phpstan-ignore-line
        $count = count($tokens);
        $position = 0;
        $namespace = '';

        while ($position < $count) {
            [$token, $position] = self::nextToken($tokens, $position);
            if (! is_array($token)) {
                continue;
            }

            switch ($token[0]) {
                case T_NAMESPACE:
                    [$namespace, $position] = self::parseNamespace($tokens, $position + 1, $count);
                    continue 2;
                case T_CLASS:
                    $className = self::parseClassName($tokens, $position + 1, $count);
                    if ($className !== null) {
                        /** @var string $namespace */
                        return $namespace !== '' ? $namespace . '\\' . $className : $className;
                    }
            }
        }

        return null;
    }

    /**
     * @param Tokens $tokens
     *
     * @return array{Token, int}
     */
    private static function nextToken(array $tokens, int $position): array
    {
        if (is_array($tokens[$position]) && in_array($tokens[$position][0], [T_ABSTRACT, T_FINAL], true)) {
            $position++;
        }

        return [$tokens[$position], $position + 1];
    }

    /** @param Tokens $tokens */
    private static function parseClassName(array $tokens, int $position, int $count): ?string
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if ($position >= $count || ! is_array($tokens[$position])) {
            return null;
        }

        return $tokens[$position][1];
    }

    /** @param Tokens $tokens */
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

    /**
     * @param Tokens $tokens
     *
     * @return ParserResult
     */
    private static function parseNamespace(array $tokens, int $position, int $count): array
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if (! is_array($tokens[$position])) {
            return ['', $position];
        }

        if ($tokens[$position][0] === self::T_NAME_QUALIFIED) {
            return [$tokens[$position][1], $position + 1];
        }

        return $tokens[$position][0] === T_STRING
            ? self::parseNamespaceParts($tokens, $position, $count)
            : ['', $position];
    }

    /**
     * @param Tokens $tokens
     *
     * @return ParserResult
     */
    private static function parseNamespaceParts(array $tokens, int $position, int $count): array
    {
        /** @var list<string> $namespaceParts */
        $namespaceParts = [];

        while ($position < $count) {
            $token = $tokens[$position];

            if (! is_array($token)) {
                if ($token === ';' || $token === '{') {
                    break;
                }

                $position++;
                continue;
            }

            if ($token[0] !== T_STRING) {
                $position++;
                continue;
            }

            $namespaceParts[] = $token[1];
            $position++;

            if ($position >= $count || $tokens[$position] !== '\\') {
                continue;
            }

            $namespaceParts[] = '\\';
            $position++;
        }

        return [implode('', $namespaceParts), $position];
    }
}
