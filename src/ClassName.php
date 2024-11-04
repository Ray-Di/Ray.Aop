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
 * @psalm-immutable
 */
final class ClassName
{
    private const T_NAME_QUALIFIED = 316;

    /**
     * Extract fully qualified class name from file
     *
     * @psalm-return string|null
     */
    public static function from(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        /** @var string */
        $namespace = '';
        /** @var string|null */
        $className = null;
        /** @var array<int, array{0: int, 1: string, 2: int}|string> $tokens */
        $tokens = token_get_all(file_get_contents($filePath)); // @phpstan-ignore-line
        $count = count($tokens);

        $i = 0;
        while ($i < $count) {
            /** @var array{0: int, 1: string, 2: int}|string $token */
            $token = $tokens[$i];

            if (is_array($token) && $token[0] === T_NAMESPACE) {
                $i++;
                while ($i < $count && is_array($tokens[$i]) && $tokens[$i][0] === T_WHITESPACE) {
                    $i++;
                }

                /** @var array{0: int, 1: string, 2: int}|string $token */
                $token = $tokens[$i];
                if (is_array($token)) {
                    if ($token[0] === self::T_NAME_QUALIFIED) {
                        /** @var string */
                        $namespace = $token[1];
                    } elseif ($token[0] === T_STRING) {
                        /** @var list<string> $namespaceParts */
                        $namespaceParts = [];
                        while ($i < $count) {
                            /** @var array{0: int, 1: string, 2: int}|string $token */
                            $token = $tokens[$i];
                            if (is_array($token) && $token[0] === T_STRING) {
                                $namespaceParts[] = $token[1];
                                $i++;
                                if ($i < $count && $tokens[$i] === '\\') {
                                    $namespaceParts[] = '\\';
                                    $i++;
                                }

                                continue;
                            }

                            if ($token === ';' || $token === '{') {
                                break;
                            }

                            $i++;
                        }

                        /** @var string */
                        $namespace = implode('', $namespaceParts);
                    }
                }

                continue;
            }

            if (is_array($token) && in_array($token[0], [T_ABSTRACT, T_FINAL], true)) {
                $i++;
                continue;
            }

            if (is_array($token) && $token[0] === T_CLASS) {
                $i++;
                while (
                    $i < $count &&
                    is_array($tokens[$i]) &&
                    in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)
                ) {
                    $i++;
                }

                if ($i < $count && is_array($tokens[$i])) {
                    /** @var string */
                    $className = $tokens[$i][1];
                    break;
                }
            }

            $i++;
        }

        if ($className === null) {
            return null;
        }

        /** @var string */
        return $namespace !== '' ? $namespace . '\\' . $className : $className;
    }
}
