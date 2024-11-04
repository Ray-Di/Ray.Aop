<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;

class ClassListTest extends TestCase
{
    public function testGetIterator(): void
    {
        $classList = new ClassList(__DIR__ . '/../src');
        $classes = iterator_to_array($classList->getIterator());

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class));
        }
    }
}
