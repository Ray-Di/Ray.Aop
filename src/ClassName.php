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
use const T_NAME_FULLY_QUALIFIED;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_STRING;
use const T_WHITESPACE;

/**
 * Extracting the fully qualified class name from a given PHP file
 *
 * @psalm-immutable
 */
final class ClassName
{
    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $namespace = '';
        $className = null;
        $tokens = token_get_all(file_get_contents($filePath)); // @phpstan-ignore-line
        $count = count($tokens);

        $i = 0;
        while ($i < $count) {
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $i++;
                while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                    $i++;
                }

                $namespaceParts = [];
                while ($i < $count) {
                    $token = $tokens[$i];

                    if (is_array($token) && in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED])) {
                        $namespaceParts[] = $token[1];
                        $i++;
                        continue;
                    }

                    if ($token === ';' || $token === '{') {
                        $i++;
                        break;
                    }

                    break;
                }

                $namespace = implode('', $namespaceParts);
                continue;
            }

            // クラス修飾子をスキップ（T_FINAL、T_ABSTRACT）
            if (is_array($token) && in_array($token[0], [T_ABSTRACT, T_FINAL])) {
                $i++;
                continue;
            }

            // クラス名の取得
            if (is_array($token) && $token[0] === T_CLASS) {
                // ホワイトスペースとコメントをスキップ
                $i++;
                while (
                    $i < $count &&
                    is_array($tokens[$i]) &&
                    in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT])
                ) {
                    $i++;
                }

                // クラス名を取得
                if ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_STRING) {
                    $className = $tokens[$i][1];
                    break;
                }
            }

            $i++;
        }

        if ($className !== null) {
            return $namespace ? $namespace . '\\' . $className : $className;
        }

        return null;
    }
}
