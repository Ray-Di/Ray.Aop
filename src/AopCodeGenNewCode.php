<?php

declare(strict_types=1);

namespace Ray\Aop;

use function preg_replace;
use function preg_replace_callback;
use function rtrim;

final class AopCodeGenNewCode
{
    /** @var string */
    private $code = '';

    /** @var int  */
    private $curlyBraceCount = 0;

    /** @return void */
    public function add(string $text)
    {
        if ($text === '{') {
            $this->curlyBraceCount++;
        }

        if ($text === '}') {
            $this->curlyBraceCount--;
        }

        $this->code .= $text;
    }

    public function insert(string $code): void
    {
        $replacement = $code . '}';
        $this->code = preg_replace('/}\s*$/', $replacement, $this->code);
    }

    public function implementsInterface(string $interfaceName): void
    {
        $pattern = '/(class\s+\w+\s+(?:extends\s+\w+\s+)?)(implements\s+\w+(?:,\s*\w+)*)?/';

        $this->code = preg_replace_callback($pattern, static function ($matches) use ($interfaceName) {
            if (isset($matches[2])) {
                // 既に implements が存在する場合
                return $matches[1] . $matches[2] . ', \\' . $interfaceName;
            }

            // implements が存在しない場合
            return rtrim($matches[1], "\n") . 'implements \\' . $interfaceName;
        }, $this->code);
    }

    public function getCodeText(): string
    {
        while ($this->curlyBraceCount !== 0) {
            $this->code .= '}';
            $this->curlyBraceCount--;
        }

        return $this->code;
    }
}
