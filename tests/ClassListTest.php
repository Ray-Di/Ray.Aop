<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_put_contents;
use function glob;
use function iterator_to_array;
use function mkdir;
use function rmdir;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class ClassListTest extends TestCase
{
    /** @var string */
    private $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/class_list_test_' . uniqid();
        if (file_exists($this->tempDir)) {
            return;
        }

        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempDir . '/*.php') as $file) { // @phpstan-ignore-line
            unlink($file);
        }

        rmdir($this->tempDir);
    }

    public function testFromReturnsNullWhenNoClass(): void
    {
        $code = <<<'PHP'
<?php
echo "Hello";
PHP;
        $file = $this->tempDir . '/Test.php';
        file_put_contents($file, $code);

        $this->assertNull(ClassList::from($file));
    }

    public function testIteratorYieldsAvailableClasses(): void
    {
        // Available class
        $code1 = <<<'PHP'
<?php
namespace Test\Space;
class AvailableClass {}
PHP;
        file_put_contents($this->tempDir . '/Available.php', $code1);
        eval(substr($code1, 5));

        // Class that won't be loaded
        $code2 = <<<'PHP'
<?php
namespace Test\Space;
class UnknownClass {}
PHP;
        file_put_contents($this->tempDir . '/Unknown.php', $code2);

        // Non-class file
        $code3 = <<<'PHP'
<?php
echo "Hello";
PHP;
        file_put_contents($this->tempDir . '/skip.php', $code3);

        $classList = new ClassList($this->tempDir);
        $classes = iterator_to_array($classList);

        $this->assertSame(['Test\\Space\\AvailableClass'], $classes);
    }

    public function testEmptyDirectory(): void
    {
        $classList = new ClassList($this->tempDir);
        $this->assertCount(0, iterator_to_array($classList));
    }

    public function testWithDeclare(): void
    {
        $code = <<<'PHP'
<?php
declare(strict_types=1);
namespace Test\Space;
class DeclaredClass {}
PHP;
        file_put_contents($this->tempDir . '/Declared.php', $code);
        eval(substr($code, 5));

        $classList = new ClassList($this->tempDir);
        $classes = iterator_to_array($classList);
        $this->assertSame(['Test\\Space\\DeclaredClass'], $classes);
    }
}
