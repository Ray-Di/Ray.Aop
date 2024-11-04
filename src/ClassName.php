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

use function var_dump;
use const T_ABSTRACT;
use const T_CLASS;
use const T_COMMENT;
use const T_DOC_COMMENT;
use const T_FINAL;
use const T_NAMESPACE;
use const T_STRING;
use const T_NS_SEPARATOR;
use const T_WHITESPACE;

/**
 * @psalm-type TokenValue = array{0: int, 1: string, 2: int}
 * @psalm-type Token = TokenValue|string
 * @psalm-type Tokens = array<int, Token>
 * @psalm-type ParserResult = array{string, int}
 */
final class ClassName
{
    /** T_NAME_QUALIFIED is available in PHP >= 8.0 */
    private const T_NAME_QUALIFIED = 316;

    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        /** @var Tokens $tokens */
        $tokens = token_get_all(file_get_contents($filePath));
        var_dump($tokens);
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
                    $namespaceResult = self::parseNamespaceDeep($tokens, (int) $position + 1, $count);
                    /** @var string $namespace */
                    $namespace = $namespaceResult[0];
                    $position = $namespaceResult[1];
                    continue 2;
                case T_CLASS:
                    $className = self::parseClassName($tokens, (int) $position + 1, $count);
                    if ($className !== null) {
                        $fqn =  $namespace !== '' ? $namespace . '\\' . $className : $className;
                        echo "FQN: $fqn" . PHP_EOL;

                        return $fqn;
                    }
            }
        }

        return null;
    }

    /**
     * @param Tokens $tokens
     * @return ParserResult
     */
    private static function parseNamespaceDeep(array $tokens, int $position, int $count): array
    {
        $position = self::skipWhitespace($tokens, $position, $count);
        if (! is_array($tokens[$position])) {
            return ['', $position];
        }

        /** @var list<string> $namespaceParts */
        $namespaceParts = [];
        $continuing = true;

        while ($position < $count && $continuing) {
            $token = $tokens[$position];

            // 文字列トークンの処理
            if (is_string($token)) {
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
                case self::T_NAME_QUALIFIED:
                    // PHP 8.0+ での完全修飾名の直接取得
                    return [$token[1], $position + 1];
                case T_STRING:
                    // 名前空間の部分を収集
                    $namespaceParts[] = $token[1];
                    break;
                case T_NS_SEPARATOR:
                    // 名前空間セパレータの処理
                    $namespaceParts[] = '\\';
                    break;
                case T_WHITESPACE:
                    // スペースは無視
                    break;
                default:
                    // その他のトークンは無視
                    break;
            }

            $position++;
        }

        // 名前空間文字列の構築
        $namespace = implode('', $namespaceParts);
        // 先頭と末尾の余分な \ を除去
        $namespace = trim($namespace, '\\');

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
