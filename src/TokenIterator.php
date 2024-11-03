<?php

declare(strict_types=1);

namespace Ray\Aop;

use ArrayIterator;

use function is_array;

/**
 * @template TKey of array-key
 * @template TValue of array{int, string, int}|string
 * @template-extends ArrayIterator<TKey, TValue>
 */
final class TokenIterator extends ArrayIterator
{
    /**
     * @return array{int, string}
     *
     * @psalm-external-mutation-free
     */
    public function getToken(): array
    {
        /** @var array{int, string, int}|string $token */
        $token = $this->current();

        return is_array($token) ? [$token[0], $token[1]] : [0, $token];
    }

    /** @psalm-external-mutation-free  */
    public function skipExtends(): void
    {
        $this->next();  // Skip extends keyword
        $this->next();  // Skip parent class name
        $this->next();  // Skip space
    }
}
