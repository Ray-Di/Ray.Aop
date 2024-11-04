<?php

declare(strict_types=1);

namespace Ray\Aop;

use function count;
use function file_exists;
use function file_get_contents;
use function implode;
use function in_array;
use function is_array;
use function is_string;
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
        $tokens = token_get_all(file_get_contents($filePath)); // @phpstan-ignore-line
        $count = count($tokens);
        $position = 0;
        $namespace = '';

        while ($position < $count) {
            /** @var array{Token, int} $result */
            $result = self::nextToken($tokens, $position);
            /** @var Token $token */
            [$token, $position] = $result;
            if (! is_array($token)) {
                continue;
            }

            switch ($token[0]) {
                case T_NAMESPACE:
                    /** @var ParserResult $namespaceResult */
                    $namespaceResult = self::parseNamespaceUnified($tokens, $position + 1, $count);
                    [$namespace, $position] = $namespaceResult;
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
     * @return ParserResult
     */
    private static function parseNamespaceUnified(array $tokens, int $position, int $count): array
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if (! is_array($tokens[$position])) {
            return ['', $position];
        }

        // PHP 8.0+ での T_NAME_QUALIFIED トークンのチェック
        if ($tokens[$position][0] === self::T_NAME_QUALIFIED) {
            /** @var TokenValue $token */
            $token = $tokens[$position];
            return [$token[1], $position + 1];
        }

        // 従来の方式でのnamespace解析
        /** @var list<string> $namespaceParts */
        $namespaceParts = [];
        $continuing = true;

        while ($position < $count && $continuing) {
            $token = $tokens[$position];

            if (! is_array($token)) {
                if ($token === ';' || $token === '{') {
                    $continuing = false;
                } elseif ($token === '\\') {
                    $namespaceParts[] = '\\';
                }
                $position++;
                continue;
            }

            /** @var TokenValue $token */
            switch ($token[0]) {
                case T_STRING:
                    $namespaceParts[] = $token[1];
                    break;
                case T_WHITESPACE:
                    // スペースは無視
                    break;
                default:
                    if ($token[0] === self::T_NAME_QUALIFIED) {
                        return [$token[1], $position + 1];
                    }
                    break;
            }

            $position++;
        }

        $namespace = trim(implode('', $namespaceParts), '\\');
        return [$namespace, $position];
    }

    /**
     * @param Tokens $tokens
     * @return array{Token, int}
     */
    private static function nextToken(array $tokens, int $position): array
    {
        if (is_array($tokens[$position]) && in_array($tokens[$position][0], [T_ABSTRACT, T_FINAL], true)) {
            $position++;
        }

        return [$tokens[$position], $position + 1];
    }

    /**
     * @param Tokens $tokens
     */
    private static function parseClassName(array $tokens, int $position, int $count): ?string
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if ($position >= $count || ! is_array($tokens[$position])) {
            return null;
        }

        /** @var TokenValue $token */
        $token = $tokens[$position];
        return $token[1];
    }

    /**
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
