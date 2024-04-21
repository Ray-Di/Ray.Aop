<?php

declare(strict_types=1);


use PHPUnit\Framework\TestCase;
use Ray\Aop\ParserFactory;

class ParserFactoryTest extends TestCase
{
    public function testCreate(): void
    {
        $this->assertInstanceOf('PhpParser\Parser', (new ParserFactory())->newInstance());
    }
}
