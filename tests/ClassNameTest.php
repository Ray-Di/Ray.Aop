<?php

declare(strict_types=1);

namespace Ray\Aop;

use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_put_contents;
use function glob;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

class ClassNameTest extends TestCase
{
    /** @var string  */
    private $tempDir;

    protected function setUp(): void
    {
        // 一時ディレクトリを作成
        $this->tempDir = sys_get_temp_dir() . '/classname_test_' . uniqid();
        if (file_exists($this->tempDir)) {
            return;
        }

        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        // 一時ディレクトリを削除
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir($this->tempDir);
    }

    public function testFileDoesNotExist(): void
    {
        $result = ClassName::from($this->tempDir . '/non_existent_file.php');
        $this->assertNull($result);
    }

    public function testClassWithoutNamespace(): void
    {
        $phpCode = <<<'PHP'
<?php
class MyClass {}
PHP;
        $filePath = $this->tempDir . '/MyClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyClass', $result);
    }

    public function testClassWithNamespace(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
class MyClass {}
PHP;
        $filePath = $this->tempDir . '/MyClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }

    public function testClassWithNamespaceAndModifiers(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
final class MyClass {}
PHP;
        $filePath = $this->tempDir . '/MyClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }

    public function testAbstractClassWithNamespace(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
abstract class MyClass {}
PHP;
        $filePath = $this->tempDir . '/MyClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }

    public function testNoClassInFile(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
PHP;
        $filePath = $this->tempDir . '/NoClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertNull($result);
    }

    public function testMultipleNamespaces(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace FirstNamespace {
    class FirstClass {}
}
namespace SecondNamespace {
    class SecondClass {}
}
PHP;
        $filePath = $this->tempDir . '/MultipleNamespaces.php';
        file_put_contents($filePath, $phpCode);

        // 最初に見つかったクラスを取得する
        $result = ClassName::from($filePath);
        $this->assertSame('FirstNamespace\\FirstClass', $result);
    }

    public function testAnonymousClass(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
$object = new class {};
PHP;
        $filePath = $this->tempDir . '/AnonymousClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertNull($result);
    }

    public function testClassWithCommentedOutCode(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
// class CommentedOutClass {}
class ActualClass {}
PHP;
        $filePath = $this->tempDir . '/CommentedClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\ActualClass', $result);
    }

    public function testInterfaceInsteadOfClass(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
interface MyInterface {}
PHP;
        $filePath = $this->tempDir . '/Interface.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertNull($result);
    }

    public function testTraitInsteadOfClass(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
trait MyTrait {}
PHP;
        $filePath = $this->tempDir . '/Trait.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertNull($result);
    }

    public function testClassWithDifferentTokenOrder(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
class /* comment */ MyClass {}
PHP;
        $filePath = $this->tempDir . '/CommentedClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }

    public function testClassWithoutNamespaceAndModifiers(): void
    {
        $phpCode = <<<'PHP'
<?php
final class MyClass {}
PHP;
        $filePath = $this->tempDir . '/FinalClass.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyClass', $result);
    }

    public function testClassWithNamespaceAndComments(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace MyNamespace;
// This is a comment
class MyClass {}
PHP;
        $filePath = $this->tempDir . '/ClassWithComments.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }

    public function testClassWithNamespaceAndWhitespace(): void
    {
        $phpCode = <<<'PHP'
<?php
namespace   MyNamespace    ;
class     MyClass    {}
PHP;
        $filePath = $this->tempDir . '/ClassWithWhitespace.php';
        file_put_contents($filePath, $phpCode);

        $result = ClassName::from($filePath);
        $this->assertSame('MyNamespace\\MyClass', $result);
    }
}
