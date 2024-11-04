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
 * @psalm-type TokenValue = array{0: int, 1: string, 2: int}
 * @psalm-type Token = TokenValue|string
 * @psalm-type Tokens = array<int, Token>
 */
final class ClassName
{
    /** @var array<int, int> */
    private const NAMESPACE_TOKENS = [
        316,  // T_NAME_QUALIFIED (PHP 8.0+)
        375,  // Alternative T_NAME_QUALIFIED on some platforms
    ];

    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        /** @var Tokens $tokens */
        $tokens = token_get_all(file_get_contents($filePath));
        $position = 0;
        $namespace = '';

        while ($position < count($tokens)) {
            $token = $tokens[$position];
            if (! is_array($token)) {
                $position++;
                continue;
            }

            if ($token[0] === T_NAMESPACE) {
                $namespace = self::parseNamespace($tokens, $position + 1);
                $position++;
                continue;
            }

            if ($token[0] === T_CLASS) {
                $className = self::parseClassName($tokens, $position + 1);
                if ($className === null) {
                    return null;
                }

                return $namespace === '' ? $className : $namespace . '\\' . $className;
            }

            $position++;
        }

        return null;
    }

    /**
     * @param Tokens $tokens
     */
    private static function parseNamespace(array $tokens, int $position): string
    {
        $token = $tokens[$position] ?? null;
        if ($token === null) {
            return '';
        }

        // Skip whitespace
        while (
            is_array($token)
            && $token[0] === T_WHITESPACE
            && isset($tokens[++$position])
        ) {
            $token = $tokens[$position];
        }

        // Check for qualified namespace token
        if (is_array($token) && in_array($token[0], self::NAMESPACE_TOKENS, true)) {
            return $token[1];
        }

        // Parse namespace parts
        $parts = [];
        while (isset($tokens[$position])) {
            $token = $tokens[$position];

            if (is_string($token)) {
                if ($token === ';' || $token === '{') {
                    break;
                }
                if ($token === '\\') {
                    $parts[] = '\\';
                }
            } elseif (
                is_array($token)
                && $token[0] === T_STRING
                && $token[1] !== ''
            ) {
                $parts[] = $token[1];
            }

            $position++;
        }

        return implode('', $parts);
    }

    /**
     * @param Tokens $tokens
     */
    private static function parseClassName(array $tokens, int $position): ?string
    {
        $token = $tokens[$position] ?? null;
        if ($token === null) {
            return null;
        }

        // Skip whitespace
        while (
            is_array($token)
            && $token[0] === T_WHITESPACE
            && isset($tokens[++$position])
        ) {
            $token = $tokens[$position];
        }

        return is_array($token) ? $token[1] : null;
    }
}
